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

if ($prof_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Καθηγητής δεν βρέθηκε']);
    exit;
}

$professor_data = $prof_result->fetch_assoc();
$professor_id = $professor_data['ID'];

$action = $_GET['action'] ?? '';

if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}


switch ($action) {
    case 'get_grading_list':
        getGradingList($login, $professor_id);
        break;
        
    case 'get_grading_form':
        getGradingForm($login, $_GET['thesis_id'] ?? 0, $professor_id);
        break;
        
    case 'submit_grades':
        submitGrades($login, $professor_id);
        break;
        
    case 'enable_grading':
        enableGrading($login, $_GET['thesis_id'] ?? 0, $professor_id);
        break;
        
    case 'download_thesis_file':
        downloadThesisFile($login, $_GET['thesis_id'] ?? 0, $professor_id);
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
            d.st_file IS NOT NULL as has_thesis_file,
            d.lib_link,
            CASE 
                WHEN d.professor = ? THEN 'supervisor'
                WHEN d.exam_1 = ? THEN 'examiner1'  
                WHEN d.exam_2 = ? THEN 'examiner2'
                ELSE 'none'
            END as examiner_role,
            CASE 
                WHEN EXISTS (SELECT 1 FROM grades g WHERE g.diplwm_id = d.id_diplwm) THEN 1 
                ELSE 0 
            END as grading_enabled,
            g.grade1_1, g.grade1_2, g.grade1_3, g.grade1_4,
            g.grade2_1, g.grade2_2, g.grade2_3, g.grade2_4, 
            g.grade3_1, g.grade3_2, g.grade3_3, g.grade3_4,
            g.final_grade
        FROM diplwmatiki d
        LEFT JOIN students st ON d.student = st.ID
        LEFT JOIN users u ON st.user_ID = u.ID
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE d.status = 'Ypo Eksetasi' 
        AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)
        ORDER BY CASE WHEN d.present_date IS NULL THEN 1 ELSE 0 END, d.present_date ASC, d.id_diplwm DESC
    ");
    
    $query->bind_param("iiiiii", $professor_id, $professor_id, $professor_id, $professor_id, $professor_id, $professor_id);    
    $query->execute();
    $result = $query->get_result();
    
    $theses = [];
    while ($row = $result->fetch_assoc()) {
        // Έλεγχος αν ο καθηγητής έχει ήδη βαθμολογήσει
        $has_graded = false;
        if ($row['grading_enabled']) {
            switch ($row['examiner_role']) {
                case 'supervisor':
                    $has_graded = !is_null($row['grade1_1']);
                    break;
                case 'examiner1':
                    $has_graded = !is_null($row['grade2_1']);
                    break;
                case 'examiner2':
                    $has_graded = !is_null($row['grade3_1']);
                    break;
            }
        }
        $row['has_graded'] = $has_graded;
        $theses[] = $row;
    }
    
    echo json_encode(['success' => true, 'theses' => $theses]);
}

function getGradingForm($login, $thesis_id, $professor_id) {
    if (!$thesis_id) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρο ID διπλωματικής']);
        return;
    }

    // Έλεγχος δικαιωμάτων και προσδιορισμός ρόλου
    $check_query = $login->prepare("
        SELECT 
            d.title,
            d.present_date,
            CONCAT(u.name, ' ', u.surname) as student_name,
            s.AM as student_am,
            CASE 
                WHEN d.professor = ? THEN 'supervisor'
                WHEN d.exam_1 = ? THEN 'examiner1'
                WHEN d.exam_2 = ? THEN 'examiner2'
                ELSE 'none'
            END as examiner_role,
            CONCAT(up.name, ' ', up.surname) as supervisor_name,
            CONCAT(ue1.name, ' ', ue1.surname) as examiner1_name,
            CONCAT(ue2.name, ' ', ue2.surname) as examiner2_name
        FROM diplwmatiki d
        LEFT JOIN students st ON d.student = st.ID
        LEFT JOIN users u ON st.user_ID = u.ID
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN professors prof ON d.professor = prof.ID
        LEFT JOIN users up ON prof.user_ID = up.ID
        LEFT JOIN professors p1 ON d.exam_1 = p1.ID
        LEFT JOIN users ue1 ON p1.user_ID = ue1.ID
        LEFT JOIN professors p2 ON d.exam_2 = p2.ID
        LEFT JOIN users ue2 ON p2.user_ID = ue2.ID
        WHERE d.id_diplwm = ? 
        AND d.status = 'Ypo Eksetasi'
        AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)
    ");
    
    $check_query->bind_param("iiiiiii", $professor_id, $professor_id, $professor_id, $thesis_id, $professor_id, $professor_id, $professor_id);
    $check_query->execute();
    $thesis_result = $check_query->get_result();
    $thesis = $thesis_result->fetch_assoc();
    
    if (!$thesis || $thesis['examiner_role'] === 'none') {
        echo json_encode(['success' => false, 'message' => 'Δεν έχετε δικαίωμα βαθμολόγησης αυτής της διπλωματικής']);
        return;
    }
    
    // Έλεγχος αν υπάρχουν βαθμοί
    $grades_query = $login->prepare("SELECT * FROM grades WHERE diplwm_id = ?");
    $grades_query->bind_param("i", $thesis_id);
    $grades_query->execute();
    $grades = $grades_query->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true, 
        'thesis' => $thesis, 
        'grades' => $grades,
        'examiner_role' => $thesis['examiner_role']
    ]);
}

function enableGrading($login, $thesis_id, $professor_id) {
    if (!$thesis_id) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρο ID διπλωματικής']);
        return;
    }

    // Μόνο ο επιβλέπων μπορεί να ενεργοποιήσει τη βαθμολόγηση
    $check_query = $login->prepare("
        SELECT id_diplwm FROM diplwmatiki 
        WHERE id_diplwm = ? AND professor = ? AND status = 'Ypo Eksetasi'
    ");
    $check_query->bind_param("ii", $thesis_id, $professor_id);
    $check_query->execute();
    
    if ($check_query->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Μόνο ο επιβλέπων μπορεί να ενεργοποιήσει τη βαθμολόγηση']);
        return;
    }
    
    // Έλεγχος αν υπάρχει ήδη εγγραφή βαθμών
    $existing_query = $login->prepare("SELECT diplwm_id FROM grades WHERE diplwm_id = ?");
    $existing_query->bind_param("i", $thesis_id);
    $existing_query->execute();
    
    if ($existing_query->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Η βαθμολόγηση είναι ήδη ενεργοποιημένη']);
        return;
    }
    
    // Δημιουργία κενής εγγραφής για βαθμούς
    $insert_query = $login->prepare("
        INSERT INTO grades (diplwm_id, final_grade, 
                          grade1_1, grade1_2, grade1_3, grade1_4,
                          grade2_1, grade2_2, grade2_3, grade2_4,
                          grade3_1, grade3_2, grade3_3, grade3_4)
        VALUES (?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)
    ");
    $insert_query->bind_param("i", $thesis_id);
    
    if ($insert_query->execute()) {
        echo json_encode(['success' => true, 'message' => 'Η βαθμολόγηση ενεργοποιήθηκε επιτυχώς']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα ενεργοποίησης βαθμολόγησης: ' . $login->error]);
    }
}

function submitGrades($login, $professor_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $thesis_id = $input['thesis_id'] ?? 0;
    $grades = $input['grades'] ?? [];
    $examiner_role = $input['examiner_role'] ?? '';
    
    if (!$thesis_id) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρο ID διπλωματικής']);
        return;
    }
    
    // Έλεγχος δικαιωμάτων
    $check_query = $login->prepare("
        SELECT 
            CASE 
                WHEN professor = ? THEN 'supervisor'
                WHEN exam_1 = ? THEN 'examiner1'
                WHEN exam_2 = ? THEN 'examiner2'
                ELSE 'none'
            END as role
        FROM diplwmatiki 
        WHERE id_diplwm = ? AND status = 'Ypo Eksetasi'
    ");
    $check_query->bind_param("iiii", $professor_id, $professor_id, $professor_id, $thesis_id);
    $check_query->execute();
    $role_result = $check_query->get_result()->fetch_assoc();
    
    if (!$role_result || $role_result['role'] === 'none' || $role_result['role'] !== $examiner_role) {
        echo json_encode(['success' => false, 'message' => 'Δεν έχετε δικαίωμα βαθμολόγησης']);
        return;
    }
    
    // Έλεγχος εγκυρότητας βαθμών - περιμένουμε grade1, grade2, grade3, grade4
    $required_grades = ['grade1', 'grade2', 'grade3', 'grade4'];
    foreach ($required_grades as $grade_field) {
        if (!isset($grades[$grade_field]) || !is_numeric($grades[$grade_field]) || $grades[$grade_field] < 0 || $grades[$grade_field] > 10) {
            echo json_encode(['success' => false, 'message' => "Μη έγκυρος βαθμός για το πεδίο $grade_field"]);
            return;
        }
    }
    
    // Προσδιορισμός στηλών βάσει ρόλου
    switch ($examiner_role) {
        case 'supervisor':
            $grade_columns = ['grade1_1', 'grade1_2', 'grade1_3', 'grade1_4'];
            break;
        case 'examiner1':
            $grade_columns = ['grade2_1', 'grade2_2', 'grade2_3', 'grade2_4'];
            break;
        case 'examiner2':
            $grade_columns = ['grade3_1', 'grade3_2', 'grade3_3', 'grade3_4'];
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Μη έγκυρος ρόλος εξεταστή']);
            return;
    }
    
    // Ενημέρωση βαθμών
    $query = $login->prepare("
        UPDATE grades SET 
            {$grade_columns[0]} = ?,
            {$grade_columns[1]} = ?,
            {$grade_columns[2]} = ?,
            {$grade_columns[3]} = ?
        WHERE diplwm_id = ?
    ");
    
    $query->bind_param("ddddi", 
        $grades['grade1'], $grades['grade2'], $grades['grade3'], $grades['grade4'], $thesis_id
    );
    
    if ($query->execute()) {
        // Υπολογισμός τελικού βαθμού αν όλοι έχουν βαθμολογήσει
        calculateFinalGrade($login, $thesis_id);
        
        echo json_encode(['success' => true, 'message' => 'Οι βαθμοί καταχωρήθηκαν επιτυχώς']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την καταχώρηση: ' . $login->error]);
    }
}

function calculateFinalGrade($login, $thesis_id) {
    // Ανάκτηση όλων των βαθμών
    $query = $login->prepare("
        SELECT grade1_1, grade1_2, grade1_3, grade1_4,
               grade2_1, grade2_2, grade2_3, grade2_4,
               grade3_1, grade3_2, grade3_3, grade3_4
        FROM grades WHERE diplwm_id = ?
    ");
    $query->bind_param("i", $thesis_id);
    $query->execute();
    $grades = $query->get_result()->fetch_assoc();
    
    if (!$grades) return;
    
    // Έλεγχος αν όλοι οι βαθμοί έχουν καταχωρηθεί
    $all_grades = [
        $grades['grade1_1'], $grades['grade1_2'], $grades['grade1_3'], $grades['grade1_4'],
        $grades['grade2_1'], $grades['grade2_2'], $grades['grade2_3'], $grades['grade2_4'],
        $grades['grade3_1'], $grades['grade3_2'], $grades['grade3_3'], $grades['grade3_4']
    ];
    
    foreach ($all_grades as $grade) {
        if (is_null($grade)) {
            return; // Δεν έχουν ολοκληρωθεί όλοι οι βαθμοί
        }
    }
    
    // Υπολογισμός τελικού βαθμού με τον τύπο από τη stored procedure
    $criterion1 = ($grades['grade1_1'] * 0.6 + $grades['grade1_2'] * 0.15 + $grades['grade1_3'] * 0.15 + $grades['grade1_4'] * 0.1);
    $criterion2 = ($grades['grade2_1'] * 0.6 + $grades['grade2_2'] * 0.15 + $grades['grade2_3'] * 0.15 + $grades['grade2_4'] * 0.1);
    $criterion3 = ($grades['grade3_1'] * 0.6 + $grades['grade3_2'] * 0.15 + $grades['grade3_3'] * 0.15 + $grades['grade3_4'] * 0.1);
    
    $final_grade = ($criterion1 + $criterion2 + $criterion3) / 3;
    
    // Ενημέρωση τελικού βαθμού
    $update_query = $login->prepare("UPDATE grades SET final_grade = ? WHERE diplwm_id = ?");
    $update_query->bind_param("di", $final_grade, $thesis_id);
    $update_query->execute();
}

function downloadThesisFile($login, $thesis_id, $professor_id) {
    if (!$thesis_id) {
        http_response_code(400);
        echo "Μη έγκυρο ID διπλωματικής";
        return;
    }

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
