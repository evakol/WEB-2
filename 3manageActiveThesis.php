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
$cancel = isset($data['cancel']) && $data['cancel'] === true;

try {
    $login->autocommit(FALSE);
    
    if ($cancel) {
        // Ακύρωση διπλωματικής
        $gs_cancel_number = $data['gs_cancel_number'] ?? '';
        $gs_cancel_year = $data['gs_cancel_year'] ?? date('Y');
        $reason = $data['reason'] ?? 'Κατόπιν αίτησης Φοιτητή/τριας';
        
        // Ενημέρωση διπλωματικής
        $stmt = $login->prepare("
            UPDATE diplwmatiki 
            SET status = 'Akyromeni', 
                cancel_num = ?, 
                cancel_year = ?, 
                cancel_reason = ?
            WHERE id_diplwm = ? AND status = 'Energi'
        ");
        $stmt->bind_param("sisi", $gs_cancel_number, $gs_cancel_year, $reason, $thesis_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to cancel thesis');
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
        
        $stmt2 = $login->prepare("
            INSERT INTO secretary_action (diplwm_id, secret_id, prev_status, curr_status, cancel_reason, gs_date)
            VALUES (?, ?, 'Energi', 'Akyromeni', ?, CURDATE())
        ");
        $stmt2->bind_param("iis", $thesis_id, $secretary_id, $reason);
        $stmt2->execute();
        
        $message = 'Η διπλωματική ακυρώθηκε επιτυχώς.';
        
    } else {
        // Καταχώρηση αριθμού έγκρισης ΓΣ
        $gs_approval_number = $data['gs_approval_number'] ?? '';
        $gs_approval_year = $data['gs_approval_year'] ?? date('Y');
        
        if (empty($gs_approval_number)) {
            throw new Exception('Απαιτείται ο αριθμός απόφασης ΓΣ');
        }
        
        $stmt = $login->prepare("
            UPDATE diplwmatiki 
            SET app_num = ?, 
                app_year = ?
            WHERE id_diplwm = ? AND status = 'Energi'
        ");
        $stmt->bind_param("isi", $gs_approval_number, $gs_approval_year, $thesis_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update thesis approval');
        }
        
        $message = 'Ο αριθμός έγκρισης ΓΣ καταχωρήθηκε επιτυχώς.';
    }
    
    $login->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
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