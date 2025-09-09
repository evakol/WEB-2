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
    case 'get_my_theses':
        getMyTheses($login, $professor_id);
        break;
        
    case 'get_thesis_details':
        getThesisDetails($login, $_GET['id'], $professor_id);
        break;
        
    case 'create_new_thesis':
        createNewThesis($login, $professor_id);
        break;
        
    case 'assign_thesis':
        assignThesis($login, $professor_id);
        break;
        
    case 'cancel_thesis':
        cancelThesis($login, $professor_id);
        break;
        
    case 'move_to_examination':
        moveToExamination($login, $professor_id);
        break;
        
    case 'add_note':
        addNote($login, $professor_id);
        break;
        
    case 'export_theses':
        exportTheses($login, $professor_id);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Άγνωστη ενέργεια']);
        break;
}

function getMyTheses($login, $professor_id) {
    $status_filter = $_GET['status'] ?? '';
    $role_filter = $_GET['role'] ?? '';
    
    $where_conditions = ["(d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)"];
    $params = [$professor_id, $professor_id, $professor_id];
    $param_types = "iii";
    
    if ($status_filter) {
        $where_conditions[] = "d.status = ?";
        $params[] = $status_filter;
        $param_types .= "s";
    }
    
    if ($role_filter === 'supervisor') {
        $where_conditions[] = "d.professor = ?";
        $params[] = $professor_id;
        $param_types .= "i";
    } elseif ($role_filter === 'examiner') {
        $where_conditions[] = "(d.exam_1 = ? OR d.exam_2 = ?)";
        $params[] = $professor_id;
        $params[] = $professor_id;
        $param_types .= "ii";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $query = $login->prepare("
        SELECT 
            d.id_diplwm as id,
            d.title,
            d.description,
            d.status,
            d.starting_date,
            d.present_date,
            d.present_time,
            d.present_venue,
            CONCAT(u.name, ' ', u.surname) as student_name,
            s.AM as student_am,
            CASE 
                WHEN d.professor = ? THEN 'supervisor'
                ELSE 'examiner'
            END as role,
            g.final_grade
        FROM diplwmatiki d
        LEFT JOIN students st ON d.student = st.ID
        LEFT JOIN users u ON st.user_ID = u.ID
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE $where_clause
        ORDER BY d.id_diplwm DESC
    ");
    
    // Προσθήκη του professor_id στην αρχή για το CASE statement
    array_unshift($params, $professor_id);
    $param_types = "i" . $param_types;
    
    $query->bind_param($param_types, ...$params);
    $query->execute();
    $result = $query->get_result();
    
    $theses = [];
    while ($row = $result->fetch_assoc()) {
        $theses[] = $row;
    }
    
    echo json_encode(['success' => true, 'theses' => $theses]);
}

function getThesisDetails($login, $thesis_id, $professor_id) {
    $query = $login->prepare("
        SELECT 
            d.*,
            CONCAT(student_u.name, ' ', student_u.surname) as student_name,
            s.AM as student_am,
            s.email as student_email,
            CONCAT(prof_u.name, ' ', prof_u.surname) as supervisor_name,
            CONCAT(exam1_u.name, ' ', exam1_u.surname) as examiner1_name,
            CONCAT(exam2_u.name, ' ', exam2_u.surname) as examiner2_name,
            g.final_grade,
            g.grade1_1, g.grade1_2, g.grade1_3, g.grade1_4,
            g.grade2_1, g.grade2_2, g.grade2_3, g.grade2_4,
            g.grade3_1, g.grade3_2, g.grade3_3, g.grade3_4
        FROM diplwmatiki d
        LEFT JOIN students st ON d.student = st.ID
        LEFT JOIN users student_u ON st.user_ID = student_u.ID
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN professors p ON d.professor = p.ID
        LEFT JOIN users prof_u ON p.user_ID = prof_u.ID
        LEFT JOIN professors p1 ON d.exam_1 = p1.ID
        LEFT JOIN users exam1_u ON p1.user_ID = exam1_u.ID
        LEFT JOIN professors p2 ON d.exam_2 = p2.ID
        LEFT JOIN users exam2_u ON p2.user_ID = exam2_u.ID
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE d.id_diplwm = ? 
        AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)
    ");
    
    $query->bind_param("iiii", $thesis_id, $professor_id, $professor_id, $professor_id);
    $query->execute();
    $result = $query->get_result();
    $thesis = $result->fetch_assoc();
    
    if (!$thesis) {
        echo json_encode(['success' => false, 'message' => 'Διπλωματική δεν βρέθηκε']);
        return;
    }
    
    // Λήψη σημειώσεων
    $notes_query = $login->prepare("
        SELECT n.notes, CONCAT(u.name, ' ', u.surname) as professor_name
        FROM notes n
        JOIN professors p ON n.professor = p.ID
        JOIN users u ON p.user_ID = u.ID
        WHERE n.diplwm_id = ? AND n.professor = ?
        ORDER BY n.note_id DESC
    ");
    $notes_query->bind_param("ii", $thesis_id, $professor_id);
    $notes_query->execute();
    $notes_result = $notes_query->get_result();
    
    $notes = [];
    while ($note = $notes_result->fetch_assoc()) {
        $notes[] = $note;
    }
    
    $thesis['notes'] = $notes;
    
    // Λήψη στοιχείων εξεταστικής επιτροπής
    $examiners_query = $login->prepare("
        SELECT 
            e.exam_id,
            e.status,
            e.invitation_date,
            e.response_date,
            CONCAT(u.name, ' ', u.surname) as examiner_name
        FROM examiners e
        JOIN professors p ON e.exam_id = p.ID
        JOIN users u ON p.user_ID = u.ID
        WHERE e.diplwm_id = ?
    ");
    $examiners_query->bind_param("i", $thesis_id);
    $examiners_query->execute();
    $examiners_result = $examiners_query->get_result();
    
    $examiners = [];
    while ($examiner = $examiners_result->fetch_assoc()) {
        $examiners[] = $examiner;
    }
    
    $thesis['examiners'] = $examiners;
    
    echo json_encode(['success' => true, 'thesis' => $thesis]);
}

function createNewThesis($login, $professor_id) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($title) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Παρακαλώ συμπληρώστε όλα τα απαιτούμενα πεδία']);
        return;
    }
    
    // Χειρισμός αρχείου PDF
    $pdf_content = 'file_data_placeholder';
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf'];
        $file_type = $_FILES['pdf_file']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Μόνο αρχεία PDF επιτρέπονται']);
            return;
        }
        
        $pdf_content = file_get_contents($_FILES['pdf_file']['tmp_name']);
    }
    
    $query = $login->prepare("
        INSERT INTO diplwmatiki (title, description, descr_file, professor, status)
        VALUES (?, ?, ?, ?, 'Ypo Anathesi')
    ");
    
    $query->bind_param("sssi", $title, $description, $pdf_content, $professor_id);
    
    if ($query->execute()) {
        echo json_encode(['success' => true, 'message' => 'Το θέμα δημιουργήθηκε επιτυχώς']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά τη δημιουργία: ' . $login->error]);
    }
}

function assignThesis($login, $professor_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $thesis_id = $input['thesis_id'] ?? 0;
    $student_id = $input['student_id'] ?? 0;
    
    if (!$thesis_id || !$student_id) {
        echo json_encode(['success' => false, 'message' => 'Παρακαλώ επιλέξτε θέμα και φοιτητή']);
        return;
    }
    
    // Έλεγχος ότι το θέμα ανήκει στον καθηγητή και είναι διαθέσιμο
    $check_query = $login->prepare("
        SELECT id_diplwm FROM diplwmatiki 
        WHERE id_diplwm = ? AND professor = ? AND status = 'Ypo Anathesi' AND student IS NULL
    ");
    $check_query->bind_param("ii", $thesis_id, $professor_id);
    $check_query->execute();
    
    if ($check_query->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Το θέμα δεν είναι διαθέσιμο για ανάθεση']);
        return;
    }
    
    $query = $login->prepare("
        UPDATE diplwmatiki 
        SET student = ?, starting_date = CURDATE()
        WHERE id_diplwm = ? AND professor = ?
    ");
    
    $query->bind_param("iii", $student_id, $thesis_id, $professor_id);
    
    if ($query->execute() && $query->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Το θέμα ανατέθηκε επιτυχώς']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την ανάθεση']);
    }
}

function cancelThesis($login, $professor_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $thesis_id = $input['thesis_id'] ?? 0;
    $cancel_reason = $input['cancel_reason'] ?? 'από Διδάσκοντα';
    
    // Έλεγχος δικαιωμάτων - μόνο ο επιβλέπων μπορεί να ακυρώσει
    $check_query = $login->prepare("
        SELECT id_diplwm FROM diplwmatiki 
        WHERE id_diplwm = ? AND professor = ? AND status IN ('Ypo Anathesi', 'Energi')
    ");
    $check_query->bind_param("ii", $thesis_id, $professor_id);
    $check_query->execute();
    
    if ($check_query->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν έχετε δικαίωμα ακύρωσης αυτής της διπλωματικής']);
        return;
    }
    
    $query = $login->prepare("
        UPDATE diplwmatiki 
        SET status = 'Akyromeni', cancel_reason = ? 
        WHERE id_diplwm = ? AND professor = ?
    ");
    
    $query->bind_param("sii", $cancel_reason, $thesis_id, $professor_id);
    
    if ($query->execute()) {
        // Ακύρωση προσκλήσεων εξεταστών
        $cancel_invitations = $login->prepare("
            UPDATE examiners 
            SET status = 'Akirwmeni' 
            WHERE diplwm_id = ? AND status = 'Energi'
        ");
        $cancel_invitations->bind_param("i", $thesis_id);
        $cancel_invitations->execute();
        
        echo json_encode(['success' => true, 'message' => 'Η διπλωματική ακυρώθηκε επιτυχώς']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την ακύρωση']);
    }
}

function moveToExamination($login, $professor_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $thesis_id = $input['thesis_id'] ?? 0;
    
    // Έλεγχος ότι ο καθηγητής είναι επιβλέπων
    $check_query = $login->prepare("
        SELECT id_diplwm FROM diplwmatiki 
        WHERE id_diplwm = ? AND professor = ? AND status = 'Energi'
    ");
    $check_query->bind_param("ii", $thesis_id, $professor_id);
    $check_query->execute();
    
    if ($check_query->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Η διπλωματική δεν βρέθηκε ή δεν έχετε δικαίωμα τροποποίησης']);
        return;
    }
    
    $query = $login->prepare("
        UPDATE diplwmatiki 
        SET status = 'Ypo Eksetasi' 
        WHERE id_diplwm = ? AND professor = ?
    ");
    
    $query->bind_param("ii", $thesis_id, $professor_id);
    
    if ($query->execute() && $query->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Η διπλωματική μεταφέρθηκε στη φάση εξέτασης']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την ενημέρωση']);
    }
}

function addNote($login, $professor_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $thesis_id = $input['thesis_id'] ?? 0;
    $note_text = $input['note'] ?? '';
    
    if (strlen($note_text) > 300) {
        echo json_encode(['success' => false, 'message' => 'Η σημείωση δεν μπορεί να υπερβαίνει τους 300 χαρακτήρες']);
        return;
    }
    
    // Έλεγχος ότι ο καθηγητής συμμετέχει στη διπλωματική
    $check_query = $login->prepare("
        SELECT id_diplwm FROM diplwmatiki 
        WHERE id_diplwm = ? AND (professor = ? OR exam_1 = ? OR exam_2 = ?) AND status = 'Energi'
    ");
    $check_query->bind_param("iiii", $thesis_id, $professor_id, $professor_id, $professor_id);
    $check_query->execute();
    
    if ($check_query->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν έχετε δικαίωμα προσθήκης σημείωσης']);
        return;
    }
    
    $query = $login->prepare("
        INSERT INTO notes (diplwm_id, professor, notes)
        VALUES (?, ?, ?)
    ");
    
    $query->bind_param("iis", $thesis_id, $professor_id, $note_text);
    
    if ($query->execute()) {
        echo json_encode(['success' => true, 'message' => 'Η σημείωση προστέθηκε επιτυχώς']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την προσθήκη σημείωσης']);
    }
}

function exportTheses($login, $professor_id) {
    $format = $_GET['format'] ?? 'csv';
    $status = $_GET['status'] ?? '';
    $role = $_GET['role'] ?? '';
    
    $where_conditions = ["(d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)"];
    $params = [$professor_id, $professor_id, $professor_id];
    $param_types = "iii";
    
    if ($status) {
        $where_conditions[] = "d.status = ?";
        $params[] = $status;
        $param_types .= "s";
    }
    
    if ($role === 'supervisor') {
        $where_conditions[] = "d.professor = ?";
        $params[] = $professor_id;
        $param_types .= "i";
    } elseif ($role === 'examiner') {
        $where_conditions[] = "(d.exam_1 = ? OR d.exam_2 = ?)";
        $params[] = $professor_id;
        $params[] = $professor_id;
        $param_types .= "ii";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $query = $login->prepare("
        SELECT 
            d.title as 'Τίτλος',
            d.status as 'Κατάσταση',
            CONCAT(u.name, ' ', u.surname) as 'Φοιτητής',
            s.AM as 'ΑΜ',
            d.starting_date as 'Ημ/νία Έναρξης',
            d.present_date as 'Ημ/νία Παρουσίασης',
            g.final_grade as 'Βαθμός',
            CASE 
                WHEN d.professor = ? THEN 'Επιβλέπων'
                ELSE 'Εξεταστής'
            END as 'Ρόλος'
        FROM diplwmatiki d
        LEFT JOIN students st ON d.student = st.ID
        LEFT JOIN users u ON st.user_ID = u.ID
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE $where_clause
        ORDER BY d.starting_date DESC
    ");
    
    array_unshift($params, $professor_id);
    $param_types = "i" . $param_types;
    
    $query->bind_param($param_types, ...$params);
    $query->execute();
    $result = $query->get_result();
    
    $data = [];
    $headers = [];
    $first_row = true;
    
    while ($row = $result->fetch_assoc()) {
        if ($first_row) {
            $headers = array_keys($row);
            $first_row = false;
        }
        $data[] = $row;
    }
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="my_theses_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM για σωστή εμφάνιση ελληνικών στο Excel
        fwrite($output, "\xEF\xBB\xBF");
        
        // Headers
        fputcsv($output, $headers);
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }
        
        fclose($output);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="my_theses_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>