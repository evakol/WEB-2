<?php
session_start();
require 'connect.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

$query = $login->prepare("SELECT ID, password, role FROM users WHERE username = ?");
if (!$query) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $login->error]);
    exit;
}

$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $query->error]);
    exit;
}

$user = $result->fetch_assoc();

if ($user) {
    if ($user['password'] === $password) {   // plain text check
        $_SESSION['user_id']   = $user['ID'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['username']  = $username;

        $redirect = match ($user['role']) {
            'Student'   => 'Student',
            'Professor' => 'Professor',
            'Secretary' => 'Secretary',
            default     => null,
        };

        if ($redirect) {
            echo json_encode(['success' => true, 'redirect' => $redirect]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Μη εξουσιοδοτημένος ρόλος.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Λάθος κωδικός.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Ο χρήστης δεν βρέθηκε.']);
}
?>
