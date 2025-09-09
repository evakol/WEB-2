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
    
    // Validation
    $required_fields = ['street', 'street_num', 'city', 'postcode', 'email', 'mobile_phone'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Το πεδίο $field είναι υποχρεωτικό"]);
            exit;
        }
    }
    
    // Email validation
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρη διεύθυνση email']);
        exit;
    }
    
    // Ενημέρωση στοιχείων φοιτητή
    $query = "
        UPDATE students 
        SET street = ?, 
            street_num = ?, 
            city = ?, 
            postcode = ?, 
            email = ?, 
            mobile_phone = ?, 
            landline_phone = ?
        WHERE user_ID = ?
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param(
        "sisssssi",
        $data['street'],
        $data['street_num'],
        $data['city'],
        $data['postcode'],
        $data['email'],
        $data['mobile_phone'],
        $data['landline_phone'] ?? null,
        $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Τα στοιχεία ενημερώθηκαν επιτυχώς'
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