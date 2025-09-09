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
    
    // Ερώτημα για τα στοιχεία του φοιτητή
    $query = "
        SELECT 
            u.name,
            u.surname,
            s.AM,
            s.street,
            s.street_num,
            s.city,
            s.postcode,
            s.email,
            s.mobile_phone,
            s.landline_phone
        FROM users u
        JOIN students s ON u.ID = s.user_ID
        WHERE u.ID = ?
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
        exit;
    }
    
    $profile = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'profile' => $profile
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>