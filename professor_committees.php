<?php
session_start();
require '../connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    echo json_encode(['success' => false, 'message' => 'Μη εξουσιοδοτημένη πρόσβαση']);
    exit;
}

$user_id = $_SESSION['user_id'];
$prof_query = $login->prepare("SELECT ID FROM professors WHERE user_ID = ?");
$prof_query->bind_param("i", $user_id);
$prof_query->execute();
$professor_id = $prof_query->get_result()->fetch_assoc()['ID'];

$action = $_GET['action'] ?? '';

if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

switch ($action) {
    case 'get_invitations':
        getCommitteeInvitations($login, $professor_id);
        break;
    case 'respond_invitation':
        respondToInvitation($login, $professor_id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Άγνωστη ενέργεια']);
        break;
}

function getCommitteeInvitations($login, $professor_id) {
    $query = $login->prepare("
        SELECT 
            e.diplwm_id,
            e.invitation_date,
            e.status,
            d.title as thesis_title,
            d.description,
            CONCAT(student_u.name, ' ', student_u.surname) as student_name,
            s.AM as student_am,
            CONCAT(prof_u.name, ' ', prof_u.surname) as supervisor_name
        FROM examiners e
        JOIN diplwmatiki d ON e.diplwm_id = d.id_diplwm
        LEFT JOIN students st ON d.student = st.ID
        LEFT JOIN users student_u ON st.user_ID = student_u.ID
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN professors p ON d.professor = p.ID
        LEFT JOIN users prof_u ON p.user_ID = prof_u.ID
        WHERE e.exam_id = ? AND e.status = 'Energi'
        ORDER BY e.invitation_date DESC
    ");
    
    $query->bind_param("i", $professor_id);
    $query->execute();
    $result = $query->get_result();
    
    $invitations = [];
    while ($row = $result->fetch_assoc()) {
        $invitations[] = $row;
    }
    
    echo json_encode(['success' => true, 'invitations' => $invitations]);
}

function respondToInvitation($login, $professor_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $diplwm_id = $input['diplwm_id'] ?? 0;
    $response = $input['response'] ?? '';
    
    if (!in_array($response, ['accept', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρη απάντηση']);
        return;
    }
    
    $status = $response === 'accept' ? 'Apodexthike' : 'Aporifthike';
    $response_date = date('Y-m-d');
    
    $check_query = $login->prepare("
        SELECT diplwm_id FROM examiners 
        WHERE diplwm_id = ? AND exam_id = ? AND status = 'Energi'
    ");
    $check_query->bind_param("ii", $diplwm_id, $professor_id);
    $check_query->execute();
    
    if ($check_query->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Η πρόσκληση δεν βρέθηκε ή έχει ήδη απαντηθεί']);
        return;
    }
    
    $query = $login->prepare("
        UPDATE examiners 
        SET status = ?, response_date = ? 
        WHERE diplwm_id = ? AND exam_id = ? AND status = 'Energi'
    ");
    
    $query->bind_param("ssii", $status, $response_date, $diplwm_id, $professor_id);
    
    if ($query->execute() && $query->affected_rows > 0) {
        $message = $response === 'accept' ? 'Η πρόσκληση έγινε δεκτή' : 'Η πρόσκληση απορρίφθηκε';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την ενημέρωση']);
    }
}
?>
