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
    
    // Βρίσκουμε τη διπλωματική του φοιτητή
    $thesis_query = "
        SELECT id_diplwm, title, status, present_date, present_time, present_venue, lib_link
        FROM diplwmatiki 
        WHERE student = ?
    ";
    
    $stmt = $login->prepare($thesis_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => true, 'thesis' => null]);
        exit;
    }
    
    $thesis = $result->fetch_assoc();
    $thesis['status'] = trim($thesis['status']);
    
    $response = ['success' => true, 'thesis' => $thesis];
    
    // Αν είναι υπό ανάθεση, παίρνουμε διαθέσιμους καθηγητές
    if ($thesis['status'] === 'Ypo Anathesi') {
        $prof_query = "
            SELECT p.ID, CONCAT(u.name, ' ', u.surname) as name, u.name as fname, u.surname
            FROM professors p
            JOIN users u ON p.user_ID = u.ID
            WHERE p.ID != (SELECT professor FROM diplwmatiki WHERE id_diplwm = ?)
            ORDER BY u.surname, u.name
        ";
        
        $prof_stmt = $login->prepare($prof_query);
        $prof_stmt->bind_param("i", $thesis['id_diplwm']);
        $prof_stmt->execute();
        $prof_result = $prof_stmt->get_result();
        
        $professors = [];
        while ($row = $prof_result->fetch_assoc()) {
            $professors[] = $row;
        }
        
        $response['available_professors'] = $professors;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>