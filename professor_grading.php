<?php
session_start();
require '../connect.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$professor_user_id = $_SESSION['user_id'];

// Βρίσκουμε το professor ID από το user ID
$prof_query = $login->prepare("SELECT ID FROM professors WHERE user_ID = ?");
$prof_query->bind_param("i", $professor_user_id);
$prof_query->execute();
$prof_result = $prof_query->get_result();
$professor_data = $prof_result->fetch_assoc();

if (!$professor_data) {
    echo json_encode(['error' => 'Professor not found']);
    exit;
}

$professor_id = $professor_data['ID'];

if ($action === 'get_grading_tasks') {
    // Παίρνουμε τις διπλωματικές που περιμένουν βαθμολόγηση
    $query = "
        SELECT DISTINCT
            d.id_diplwm,
            d.title,
            d.description,
            d.status,
            d.present_date,
            d.present_time,
            s.AM as student_am,
            u_student.name as student_name,
            u_student.surname as student_surname,
            CASE 
                WHEN d.professor = ? THEN 'supervisor'
                WHEN e.exam_id = ? THEN 'examiner'
                ELSE 'other'
            END as role_in_thesis,
            g.final_grade,
            g.grade1_1, g.grade1_2, g.grade1_3, g.grade1_4,
            g.grade2_1, g.grade2_2, g.grade2_3, g.grade2_4,
            g.grade3_1, g.grade3_2, g.grade3_3, g.grade3_4
        FROM diplwmatiki d
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN users u_student ON s.user_ID = u_student.ID
        LEFT JOIN examiners e ON d.id_diplwm = e.diplwm_id AND e.exam_id = ? AND e.status = 'Apodexthike'
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE (d.professor = ? OR e.exam_id = ?) 
        AND d.status IN ('Ypo Eksetasi', 'Peratomeni')
        AND d.present_date IS NOT NULL
        ORDER BY d.present_date DESC
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param("iiiii", $professor_id, $professor_id, $professor_id, $professor_id, $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grading_tasks = [];
    while ($row = $result->fetch_assoc()) {
        $grading_tasks[] = $row;
    }
    
    echo json_encode($grading_tasks);

} elseif ($action === 'submit_grades') {
    $diplwm_id = $input['diplwm_id'] ?? 0;
    $grades = $input['grades'] ?? [];
    
    if (!$diplwm_id || empty($grades)) {
        echo json_encode(['error' => 'Missing required data']);
        exit;
    }
    
    // Ελέγχουμε αν η διπλωματική υπάρχει και ο καθηγητής έχει δικαίωμα βαθμολόγησης
    $check_query = "
        SELECT d.*, 
               CASE 
                   WHEN d.professor = ? THEN 'supervisor'
                   WHEN e.exam_id = ? THEN 'examiner'
                   ELSE 'none'
               END as role
        FROM diplwmatiki d
        LEFT JOIN examiners e ON d.id_diplwm = e.diplwm_id AND e.exam_id = ? AND e.status = 'Apodexthike'
        WHERE d.id_diplwm = ? AND (d.professor = ? OR e.exam_id = ?)
    ";
    
    $check_stmt = $login->prepare($check_query);
    $check_stmt->bind_param("iiiiii", $professor_id, $professor_id, $professor_id, $diplwm_id, $professor_id, $professor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $thesis_data = $check_result->fetch_assoc();
    
    if (!$thesis_data || $thesis_data['role'] === 'none') {
        echo json_encode(['error' => 'Not authorized to grade this thesis']);
        exit;
    }
    
    // Ελέγχουμε αν υπάρχουν ήδη βαθμοί
    $existing_grades_query = "SELECT * FROM grades WHERE diplwm_id = ?";
    $existing_stmt = $login->prepare($existing_grades_query);
    $existing_stmt->bind_param("i", $diplwm_id);
    $existing_stmt->execute();
    $existing_result = $existing_stmt->get_result();
    
    if ($existing_result->num_rows > 0) {
        // Update existing grades
        $update_query = "
            UPDATE grades SET 
                grade1_1 = ?, grade1_2 = ?, grade1_3 = ?, grade1_4 = ?,
                grade2_1 = ?, grade2_2 = ?, grade2_3 = ?, grade2_4 = ?,
                grade3_1 = ?, grade3_2 = ?, grade3_3 = ?, grade3_4 = ?
            WHERE diplwm_id = ?
        ";
        
        $update_stmt = $login->prepare($update_query);
        $update_stmt->bind_param("dddddddddddi", 
            $grades['grade1_1'], $grades['grade1_2'], $grades['grade1_3'], $grades['grade1_4'],
            $grades['grade2_1'], $grades['grade2_2'], $grades['grade2_3'], $grades['grade2_4'],
            $grades['grade3_1'], $grades['grade3_2'], $grades['grade3_3'], $grades['grade3_4'],
            $diplwm_id
        );
        
        if ($update_stmt->execute()) {
            // Χρησιμοποιούμε την stored procedure για υπολογισμό τελικού βαθμού
            $call_proc = "CALL kataxwrisi_vathmwn(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $proc_stmt = $login->prepare($call_proc);
            $proc_stmt->bind_param("idddddddddddd", 
                $diplwm_id,
                $grades['grade1_1'], $grades['grade1_2'], $grades['grade1_3'], $grades['grade1_4'],
                $grades['grade2_1'], $grades['grade2_2'], $grades['grade2_3'], $grades['grade2_4'],
                $grades['grade3_1'], $grades['grade3_2'], $grades['grade3_3'], $grades['grade3_4']
            );
            $proc_stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Οι βαθμοί ενημερώθηκαν επιτυχώς']);
        } else {
            echo json_encode(['error' => 'Failed to update grades']);
        }
    } else {
        // Insert new grades using stored procedure
        $call_proc = "CALL kataxwrisi_vathmwn(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $proc_stmt = $login->prepare($call_proc);
        $proc_stmt->bind_param("idddddddddddd", 
            $diplwm_id,
            $grades['grade1_1'], $grades['grade1_2'], $grades['grade1_3'], $grades['grade1