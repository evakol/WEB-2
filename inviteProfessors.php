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

if (!$data || !isset($data['professor1']) || !isset($data['professor2'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
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
    
    // Βρίσκουμε τη διπλωματική υπό ανάθεση
    $thesis_query = $login->prepare("SELECT id_diplwm FROM diplwmatiki WHERE student = ? AND status = ' Ypo Anathesi'");
    $thesis_query->bind_param("i", $student_id);
    $thesis_query->execute();
    $thesis_result = $thesis_query->get_result();
    
    if ($thesis_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε διπλωματική υπό ανάθεση']);
        exit;
    }
    
    $thesis_data = $thesis_result->fetch_assoc();
    $thesis_id = $thesis_data['id_diplwm'];
    
    // Έλεγχος ότι οι καθηγητές είναι διαφορετικοί
    if ($data['professor1'] === $data['professor2']) {
        echo json_encode(['success' => false, 'message' => 'Οι καθηγητές πρέπει να είναι διαφορετικοί']);
        exit;
    }
    
    // Έλεγχος ότι δεν είναι ο επιβλέπων
    $supervisor_query = $login->prepare("SELECT professor FROM diplwmatiki WHERE id_diplwm = ?");
    $supervisor_query->bind_param("i", $thesis_id);
    $supervisor_query->execute();
    $supervisor_result = $supervisor_query->get_result();
    $supervisor_data = $supervisor_result->fetch_assoc();
    
    if ($data['professor1'] == $supervisor_data['professor'] || $data['professor2'] == $supervisor_data['professor']) {
        echo json_encode(['success' => false, 'message' => 'Ο επιβλέπων δεν μπορεί να είναι και μέλος της τριμελούς']);
        exit;
    }
    
    $login->autocommit(FALSE);
    
    try {
        // Διαγραφή παλιών προσκλήσεων αν υπάρχουν
        $delete_query = $login->prepare("DELETE FROM examiners WHERE diplwm_id = ?");
        $delete_query->bind_param("i", $thesis_id);
        $delete_query->execute();
        
        // Εισαγωγή νέων προσκλήσεων
        $invite_query = $login->prepare("
            INSERT INTO examiners (diplwm_id, exam_id, invitation_date, status) 
            VALUES (?, ?, CURDATE(), 'Energi')
        ");
        
        // Πρόσκληση 1ου καθηγητή
        $invite_query->bind_param("ii", $thesis_id, $data['professor1']);
        $invite_query->execute();
        
        // Πρόσκληση 2ου καθηγητή
        $invite_query->bind_param("ii", $thesis_id, $data['professor2']);
        $invite_query->execute();
        
        $login->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Οι προσκλήσεις στάλθηκαν επιτυχώς. Περιμένετε την αποδοχή από τους καθηγητές.'
        ]);
        
    } catch (Exception $e) {
        $login->rollback();
        throw $e;
    } finally {
        $login->autocommit(TRUE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>