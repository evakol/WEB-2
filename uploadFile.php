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

// Έλεγχος μεθόδου POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Έλεγχος αρχείου
if (!isset($_FILES['thesis_file']) || $_FILES['thesis_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Σφάλμα ανεβάσματος αρχείου']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['thesis_file'];
    
    // Βρίσκουμε το student ID
    $student_query = $login->prepare("SELECT ID FROM students WHERE user_ID = ?");
    $student_query->bind_param("i", $user_id);
    $student_query->execute();
    $student_result = $student_query->get_result();
    
    if ($student_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student record not found']);
        exit;
    }
    
    $student_data = $student_result->fetch_assoc();
    $student_id = $student_data['ID'];
    
    // Βρίσκουμε τη διπλωματική υπό εξέταση
    $thesis_query = $login->prepare("SELECT id_diplwm FROM diplwmatiki WHERE student = ? AND status = 'Ypo Eksetasi'");
    $thesis_query->bind_param("i", $student_id);
    $thesis_query->execute();
    $thesis_result = $thesis_query->get_result();
    
    if ($thesis_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε διπλωματική υπό εξέταση']);
        exit;
    }
    
    $thesis_data = $thesis_result->fetch_assoc();
    $thesis_id = $thesis_data['id_diplwm'];
    
    // Validation αρχείου
    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Επιτρέπονται μόνο αρχεία PDF, DOC, DOCX']);
        exit;
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB
        echo json_encode(['success' => false, 'message' => 'Το αρχείο δεν μπορεί να είναι μεγαλύτερο από 10MB']);
        exit;
    }
    
    // Ανάγνωση αρχείου ως binary
    $file_content = file_get_contents($file['tmp_name']);
    
    if ($file_content === false) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα ανάγνωσης αρχείου']);
        exit;
    }
    
    // Ενημέρωση βάσης δεδομένων
    $update_query = $login->prepare("UPDATE diplwmatiki SET st_file = ? WHERE id_diplwm = ?");
    $update_query->bind_param("bi", $file_content, $thesis_id);
    
    if ($update_query->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Το αρχείο ανέβηκε επιτυχώς',
            'filename' => $file['name'],
            'size' => $file['size']
        ]);
    } else {
        throw new Exception('Database update failed: ' . $login->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>