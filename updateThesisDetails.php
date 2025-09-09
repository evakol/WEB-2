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

// Ανάγνωση JSON δεδομένων
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
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
    
    // Validation για ημερομηνία
    if (!empty($data['present_date'])) {
        $present_date = DateTime::createFromFormat('Y-m-d', $data['present_date']);
        if (!$present_date) {
            echo json_encode(['success' => false, 'message' => 'Μη έγκυρη ημερομηνία']);
            exit;
        }
        
        // Έλεγχος ότι η ημερομηνία είναι μελλοντική
        $today = new DateTime();
        if ($present_date < $today) {
            echo json_encode(['success' => false, 'message' => 'Η ημερομηνία παρουσίασης πρέπει να είναι μελλοντική']);
            exit;
        }
    }
    
    // Validation για URL
    if (!empty($data['lib_link']) && !filter_var($data['lib_link'], FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρος σύνδεσμος αποθετηρίου']);
        exit;
    }
    
    // Ενημέρωση στοιχείων διπλωματικής
    $query = "
        UPDATE diplwmatiki 
        SET present_date = ?, 
            present_time = ?, 
            present_venue = ?, 
            lib_link = ?
        WHERE id_diplwm = ?
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param(
        "ssssi",
        $data['present_date'] ?: null,
        $data['present_time'] ?: null,
        $data['present_venue'] ?: null,
        $data['lib_link'] ?: null,
        $thesis_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Τα στοιχεία παρουσίασης ενημερώθηκαν επιτυχώς'
        ]);
    } else {
        throw new Exception('Update failed: ' . $login->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>