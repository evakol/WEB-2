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

if ($action === 'get_available_theses') {
    // Παίρνουμε τις διαθέσιμες διπλωματικές του καθηγητή
    $query = "
        SELECT id_diplwm, title, description 
        FROM diplwmatiki 
        WHERE professor = ? AND status = 'Ypo Anathesi' AND student IS NULL
        ORDER BY title
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $theses = [];
    while ($row = $result->fetch_assoc()) {
        $theses[] = $row;
    }
    
    echo json_encode($theses);

} elseif ($action === 'search_students') {
    $search_term = $input['search_term'] ?? '';
    
    if (strlen($search_term) < 2) {
        echo json_encode([]);
        exit;
    }
    
    // Αναζήτηση φοιτητών
    $query = "
        SELECT s.ID, s.AM, u.name, u.surname 
        FROM students s
        JOIN users u ON s.user_ID = u.ID
        WHERE s.AM LIKE ? OR CONCAT(u.name, ' ', u.surname) LIKE ?
        ORDER BY s.AM
        LIMIT 10
    ";
    
    $search_param = '%' . $search_term . '%';
    $stmt = $login->prepare($query);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode($students);

} elseif ($action === 'assign_thesis') {
    $thesis_id = $input['thesis_id'] ?? 0;
    $student_id = $input['student_id'] ?? 0;
    
    if (!$thesis_id || !$student_id) {
        echo json_encode(['error' => 'Missing required data']);
        exit;
    }
    
    // Ελέγχουμε αν η διπλωματική είναι διαθέσιμη
    $check_query = "
        SELECT * FROM diplwmatiki 
        WHERE id_diplwm = ? AND professor = ? AND status = 'Ypo Anathesi' AND student IS NULL
    ";
    $check_stmt = $login->prepare($check_query);
    $check_stmt->bind_param("ii", $thesis_id, $professor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['error' => 'Thesis not available for assignment']);
        exit;
    }
    
    // Ελέγχουμε αν ο φοιτητής έχει ήδη διπλωματική
    $student_check = "
        SELECT * FROM diplwmatiki 
        WHERE student = ? AND status IN ('Ypo Anathesi', 'Energi', 'Ypo Eksetasi')
    ";
    $student_stmt = $login->prepare($student_check);
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows > 0) {
        echo json_encode(['error' => 'Student already has an active thesis']);
        exit;
    }
    
    // Αναθέτουμε τη διπλωματική
    $assign_query = "UPDATE diplwmatiki SET student = ? WHERE id_diplwm = ?";
    $assign_stmt = $login->prepare($assign_query);
    $assign_stmt->bind_param("ii", $student_id, $thesis_id);
    
    if ($assign_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Η διπλωματική ανατέθηκε επιτυχώς']);
    } else {
        echo json_encode(['error' => 'Failed to assign thesis']);
    }
}
?>