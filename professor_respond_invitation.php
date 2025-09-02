<?php
session_start();
require '../connect.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$diplwm_id = $input['diplwm_id'] ?? 0;
$response = $input['response'] ?? ''; // 'accept' or 'reject'

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

// Ελέγχουμε αν υπάρχει η πρόσκληση
$check_query = "SELECT * FROM examiners WHERE diplwm_id = ? AND exam_id = ? AND status = 'Energi'";
$check_stmt = $login->prepare($check_query);
$check_stmt->bind_param("ii", $diplwm_id, $professor_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['error' => 'Invitation not found or already responded']);
    exit;
}

// Ενημερώνουμε την απάντηση
$new_status = ($response === 'accept') ? 'Apodexthike' : 'Aporifthike';
$response_date = date('Y-m-d');

$update_query = "UPDATE examiners SET status = ?, response_date = ? WHERE diplwm_id = ? AND exam_id = ?";
$update_stmt = $login->prepare($update_query);
$update_stmt->bind_param("ssii", $new_status, $response_date, $diplwm_id, $professor_id);

if ($update_stmt->execute()) {
    // Ελέγχουμε αν έχουμε 2 αποδοχές για αυτόματη ενεργοποίηση
    if ($response === 'accept') {
        $count_query = "SELECT COUNT(*) as accepted_count FROM examiners WHERE diplwm_id = ? AND status = 'Apodexthike'";
        $count_stmt = $login->prepare($count_query);
        $count_stmt->bind_param("i", $diplwm_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        
        if ($count_data['accepted_count'] >= 2) {
            // Ενεργοποιούμε τη διπλωματική
            $activate_query = "UPDATE diplwmatiki SET status = 'Energi', starting_date = CURDATE() WHERE id_diplwm = ?";
            $activate_stmt = $login->prepare($activate_query);
            $activate_stmt->bind_param("i", $diplwm_id);
            $activate_stmt->execute();
            
            // Απορρίπτουμε τις υπόλοιπες προσκλήσεις
            $reject_others_query = "UPDATE examiners SET status = 'Aporifthike' WHERE diplwm_id = ? AND status = 'Energi'";
            $reject_others_stmt = $login->prepare($reject_others_query);
            $reject_others_stmt->bind_param("i", $diplwm_id);
            $reject_others_stmt->execute();
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Απάντηση καταχωρήθηκε επιτυχώς']);
} else {
    echo json_encode(['error' => 'Failed to update response']);
}
?>