<?php
session_start();
require '../connect.php';

header('Content-Type: application/json; charset=utf-8');

// Έλεγχος εξουσιοδότησης
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Ανάγνωση JSON δεδομένων
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['thesis_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$thesis_id = $data['thesis_id'];

try {
    $login->autocommit(FALSE);
    
    // Έλεγχος ότι η διπλωματική είναι υπό εξέταση και έχει βαθμό και σύνδεσμο Νημερτή
    $check_stmt = $login->prepare("
        SELECT d.status, g.final_grade, d.lib_link
        FROM diplwmatiki d
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE d.id_diplwm = ?
    ");
    $check_stmt->bind_param("i", $thesis_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $thesis_data = $result->fetch_assoc();
    
    if (!$thesis_data) {
        throw new Exception('Η διπλωματική δεν βρέθηκε');
    }
    
    if ($thesis_data['status'] !== 'Ypo Eksetasi') {
        throw new Exception('Η διπλωματική δεν είναι υπό εξέταση');
    }
    
    if (empty($thesis_data['final_grade'])) {
        throw new Exception('Δεν έχει καταχωρηθεί βαθμός για τη διπλωματική');
    }
    
    if (empty($thesis_data['lib_link'])) {
        throw new Exception('Δεν έχει καταχωρηθεί σύνδεσμος Νημερτή');
    }
    
    // Ενημέρωση κατάστασης σε "Περατωμένη"
    $update_stmt = $login->prepare("
        UPDATE diplwmatiki 
        SET status = 'Peratomeni'
        WHERE id_diplwm = ? AND status = 'Ypo Eksetasi'
    ");
    $update_stmt->bind_param("i", $thesis_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Αποτυχία ενημέρωσης κατάστασης');
    }
    
    if ($update_stmt->affected_rows === 0) {
        throw new Exception('Δεν βρέθηκε διπλωματική προς ενημέρωση');
    }
    
    // Καταγραφή ενέργειας γραμματείας - ΔΙΟΡΘΩΣΗ: Χρήση secretary.ID αντί για user_id
    $secretary_user_id = $_SESSION['user_id'];
    
    // Βρίσκουμε το secretary.ID από το user_id
    $secretary_query = $login->prepare("SELECT ID FROM secretary WHERE user_id = ?");
    $secretary_query->bind_param("i", $secretary_user_id);
    $secretary_query->execute();
    $secretary_result = $secretary_query->get_result();
    
    if ($secretary_result->num_rows === 0) {
        throw new Exception('Δεν βρέθηκε εγγραφή γραμματέα');
    }
    
    $secretary_data = $secretary_result->fetch_assoc();
    $secretary_id = $secretary_data['ID'];
    
    $action_stmt = $login->prepare("
        INSERT INTO secretary_action (diplwm_id, secret_id, prev_status, curr_status, gs_date)
        VALUES (?, ?, 'Ypo Eksetasi', 'Peratomeni', CURDATE())
    ");
    $action_stmt->bind_param("ii", $thesis_id, $secretary_id);
    $action_stmt->execute();
    
    $login->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Η διπλωματική μεταφέρθηκε επιτυχώς στην κατάσταση "Περατωμένη".'
    ]);
    
} catch (Exception $e) {
    $login->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Σφάλμα: ' . $e->getMessage()
    ]);
} finally {
    $login->autocommit(TRUE);
}
?>