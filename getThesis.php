<?php
session_start();
require '../connect.php';

header('Content-Type: application/json; charset=utf-8');

// Έλεγχος εξουσιοδότησης
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Βρίσκουμε το student ID
    $student_query = $login->prepare("SELECT ID, AM FROM students WHERE user_ID = ?");
    $student_query->bind_param("i", $user_id);
    $student_query->execute();
    $student_result = $student_query->get_result();
    
    if ($student_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student record not found']);
        exit;
    }
    
    $student_data = $student_result->fetch_assoc();
    $student_id = $student_data['ID'];
    
    // Ερώτημα για τη διπλωματική του φοιτητή
    $query = "
        SELECT 
            d.id_diplwm,
            d.title,
            d.description,
            d.status,
            d.starting_date,
            d.present_date,
            d.present_time,
            d.present_venue,
            d.lib_link,
            -- Επιβλέπων
            CONCAT(up.name, ' ', up.surname) as supervisor_name,
            -- Εξεταστές
            CONCAT(ue1.name, ' ', ue1.surname) as examiner1_name,
            CONCAT(ue2.name, ' ', ue2.surname) as examiner2_name,
            -- Βαθμός
            g.final_grade
        FROM diplwmatiki d
        LEFT JOIN professors prof ON d.professor = prof.ID
        LEFT JOIN users up ON prof.user_ID = up.ID
        LEFT JOIN professors p1 ON d.exam_1 = p1.ID
        LEFT JOIN users ue1 ON p1.user_ID = ue1.ID
        LEFT JOIN professors p2 ON d.exam_2 = p2.ID
        LEFT JOIN users ue2 ON p2.user_ID = ue2.ID
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE d.student = ?
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => true, 'thesis' => null]);
        exit;
    }
    
    $thesis = $result->fetch_assoc();
    $thesis['status'] = trim($thesis['status']);
    
    // Υπολογισμός χρόνου από ανάθεση
    $time_passed = null;
    if ($thesis['starting_date']) {
        $start = new DateTime($thesis['starting_date']);
        $now = new DateTime();
        $diff = $start->diff($now);
        $time_passed = [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'total_days' => $diff->days
        ];
    }
    
    echo json_encode([
        'success' => true,
        'thesis' => $thesis,
        'time_passed' => $time_passed
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>