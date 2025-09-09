<?php
session_start();
require '../connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    echo json_encode(['success' => false, 'message' => 'Μη εξουσιοδοτημένη πρόσβαση']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Εύρεση του professor ID
$prof_query = $login->prepare("SELECT ID FROM professors WHERE user_ID = ?");
$prof_query->bind_param("i", $user_id);
$prof_query->execute();
$prof_result = $prof_query->get_result();
$professor_data = $prof_result->fetch_assoc();
$professor_id = $professor_data['ID'];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_grading_list':
        getGradingList($login, $professor_id);
        break;
        
    case 'get_grading_form':
        getGradingForm($login, $_GET['thesis_id'], $professor_id);
        break;
        
    case 'submit_grades':
        submitGrades($login, $professor_id);
        break;
        
    case 'download_thesis_file':
        downloadThesisFile($login, $_GET['thesis_id'], $professor_id);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Άγνωστη ενέργεια']);
        break;
}

function getGradingList($login, $professor_id) {
    $query = $login->prepare("
        SELECT 
            d.id_diplwm as id,
            d.title,
            d.present_date,
            d.present_time,
            d.present_venue,
            CONCAT(u.name, ' ', u.surname) as student_name,
            s.AM as student_am,
            s.email as student_email,
            d.st_file IS NOT NULL as has_thesis_file,
            d.lib_link,
            CASE 
                WHEN EXISTS (SELECT 1 FROM grades g WHERE g.diplwm_id = d.id_diplwm) THEN 1 
                ELSE 0 
            END as has_grades
        FROM diplwmatiki d
        LEFT JOIN students st ON d.student = st.ID
        LEFT JOIN users u ON st.user_ID = u.ID
        LEFT JOIN students s ON d.student = s.ID
        WHERE d.status = 'Ypo Eksetasi' 
        AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)
        ORDER BY d.present_date ASC
    ");
    
    $query->bind_param("iii", $professor_id, $professor_id, $professor_id);
    $query->execute();
    $result = $query->get_result();
    
    $theses = [];
    while ($row = $result->fetch_assoc()) {
        $theses[] = $row;
    }
    
    echo json_encode(['success' => true, 'theses' => $theses]);
}

function getGradingForm($login, $thesis_id, $professor_id) {
    // Έλεγχος δικαιωμάτων
    $check_query = $login->prepare("
        SELECT 
            d.title,
            d.present_date,
            CONCAT(u.name, ' ', u.surname) as student_name,
            s.AM as student_am,
            CASE 
                WHEN d.professor = ? THEN 'supervisor'
                ELSE 'examiner'
            END as role
        FROM diplwmatiki d
        LEFT JOIN students st ON d.student = st.ID
        LEFT JOIN users u ON st.user_ID = u.ID
        LEFT JOIN students s ON d.student = s.ID
        WHERE d.id_diplwm = ? 
        AND d.status = 'Ypo Eksetasi'
        AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)
    ");
    
    $check_query->bind_param("iiiii", $professor_id, $thesis_id, $professor_id, $professor_id, $professor_id);
    $check_query->execute();
    $thesis_result = $check_query->get_result();
    $thesis = $thesis_result->fetch_assoc();
    
    if (!$thesis) {
        echo json_encode(['success' => false, 'message' => 'Δεν έχετε δικαίωμα βαθμολόγησης αυτής της διπλωματικής']);
        return;
    }
    
    // Έλεγχος αν υπάρχουν ήδη βαθμοί
    $grades_query = $login->prepare("SELECT * FROM grades WHERE diplwm_id = ?");
    $grades_query->bind_param("i", $thesis_id);
    $grades_query->execute();
    $grades = $grades_query->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true, 
        'thesis' => $thesis, 
        'grades' => $grades
    ]);
}

function submitGrades($login, $professor_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $thesis_id = $input['thesis_id'] ?? 0;
    $grades = $input['grades'] ?? [];
    
    // Έλεγχος δικαιωμάτων
    $check_query = $login->prepare("
        SELECT id_diplwm FROM diplwmatiki 
        WHERE id_diplwm = ? 
        AND status = 'Ypo Eksetasi'
        AND (professor = ? OR exam_1 = ? OR exam_2 = ?)
    ");
    $check_query->bind_param("iiii", $thesis_id, $professor_id, $professor_id, $professor_id);
    $check_query->execute();
    
    if ($check_query->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν έχετε δικαίωμα βαθμολόγησης']);
        return;
    }
    
    // Έλεγχος εγκυρότητας βαθμών
    $required_grades = [
        'grade1_1', 'grade1_2', 'grade1_3', 'grade1_4',
        'grade2_1', 'grade2_2', 'grade2_3', 'grade2_4',
        'grade3_1', 'grade3_2', 'grade3_3', 'grade3_4'
    ];
    
    foreach ($required_grades as $grade_field) {
        if (!isset($grades[$grade_field]) || $grades[$grade_field] < 0 || $grades[$grade_field] > 10) {
            echo json_encode(['success' => false, 'message' => "Μη έγκυρος βαθμός για το πεδίο $grade_field"]);
            return;
        }
    }
    
    // Έλεγχος αν υπάρχουν ήδη βαθμοί
    $existing_query = $login->prepare("SELECT diplwm_id FROM grades WHERE diplwm_id = ?");
    $existing_query->bind_param("i", $thesis_id);
    $existing_query->execute();
    $exists = $existing_query->get_result()->fetch_assoc();
    
    if ($exists) {
        // Ενημέρωση υπαρχόντων βαθμών
        $final_grade = calculateFinalGrade($grades);
        
        $query = $login->prepare("
            UPDATE grades SET 
                final_grade = ?,
                grade1_1 = ?, grade1_2 = ?, grade1_3 = ?, grade1_4 = ?,
                grade2_1 = ?, grade2_2 = ?, grade2_3 = ?, grade2_4 = ?,
                grade3_1 = ?, grade3_2 = ?, grade3_3 = ?, grade3_4 = ?
            WHERE diplwm_id = ?
        ");
        
        $query->bind_param("dddddddddddddi", 
            $final_grade,
            $grades['grade1_1'], $grades['grade1_2'], $grades['grade1_3'], $grades['grade1_4'],
            $grades['grade2_1'], $grades['grade2_2'], $grades['grade2_3'], $grades['grade2_4'],
            $grades['grade3_1'], $grades['grade3_2'], $grades['grade3_3'], $grades['grade3_4'],
            $thesis_id
        );
    } else {
        // Χρήση της stored procedure
        $query = $login->prepare("
            CALL kataxwrisi_vathmwn(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $query->bind_param("idddddddddddd", 
            $thesis_id,
            $grades['grade1_1'], $grades['grade1_2'], $grades['grade1_3'], $grades['grade1_4'],
            $grades['grade2_1'], $grades['grade2_2'], $grades['grade2_3'], $grades['grade2_4'],
            $grades['grade3_1'], $grades['grade3_2'], $grades['grade3_3'], $grades['grade3_4']
        );
    }
    
    if ($query->execute()) {
        $final_grade = calculateFinalGrade($grades);
        echo json_encode(['success' => true, 'message' => 'Οι βαθμοί καταχωρήθηκαν επιτυχώς', 'final_grade' => round($final_grade, 2)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την καταχώρηση: ' . $login->error]);
    }
}

function calculateFinalGrade($grades) {
    $criterion1 = ($grades['grade1_1'] * 0.6 + $grades['grade1_2'] * 0.15 + $grades['grade1_3'] * 0.15 + $grades['grade1_4'] * 0.1);
    $criterion2 = ($grades['grade2_1'] * 0.6 + $grades['grade2_2'] * 0.15 + $grades['grade2_3'] * 0.15 + $grades['grade2_4'] * 0.1);
    $criterion3 = ($grades['grade3_1'] * 0.6 + $grades['grade3_2'] * 0.15 + $grades['grade3_3'] * 0.15 + $grades['grade3_4'] * 0.1);
    return ($criterion1 + $criterion2 + $criterion3) / 3;
}

function downloadThesisFile($login, $thesis_id, $professor_id) {
    // Έλεγχος δικαιωμάτων
    $query = $login->prepare("
        SELECT d.st_file, d.title
        FROM diplwmatiki d
        WHERE d.id_diplwm = ? 
        AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)
        AND d.st_file IS NOT NULL
    ");
    
    $query->bind_param("iiii", $thesis_id, $professor_id, $professor_id, $professor_id);
    $query->execute();
    $result = $query->get_result();
    $thesis = $result->fetch_assoc();
    
    if (!$thesis) {
        http_response_code(404);
        echo "Αρχείο δεν βρέθηκε ή δεν έχετε πρόσβαση";
        return;
    }
    
    $filename = "thesis_" . $thesis_id . ".pdf";
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($thesis['st_file']));
    
    echo $thesis['st_file'];
    exit;
}
?>