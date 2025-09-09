<?php
session_start();
require '../connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    echo json_encode(['success' => false, 'message' => 'Μη εξουσιοδοτημένη πρόσβαση']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Εύρεση του professor ID
$prof_query = $login->prepare("SELECT ID FROM professors WHERE user_ID = ?");
$prof_query->bind_param("i", $user_id);
$prof_query->execute();
$prof_result = $prof_query->get_result();
$professor_data = $prof_result->fetch_assoc();
$professor_id = $professor_data['ID'];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'search_students':
        searchStudents($login, $_GET['query'] ?? '');
        break;
        
    case 'get_available_theses':
        getAvailableTheses($login, $professor_id);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Άγνωστη ενέργεια']);
        break;
}

function searchStudents($login, $query) {
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'students' => []]);
        return;
    }
    
    $search = "%$query%";
    
    $stmt = $login->prepare("
        SELECT 
            s.ID, 
            s.AM,
            u.name, 
            u.surname,
            s.email,
            s.mobile_phone,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM diplwmatiki d 
                    WHERE d.student = s.ID 
                    AND d.status IN ('Ypo Anathesi', 'Energi', 'Ypo Eksetasi')
                ) THEN 1 
                ELSE 0 
            END as has_active_thesis
        FROM students s
        JOIN users u ON s.user_ID = u.ID
        WHERE (s.AM LIKE ? OR CONCAT(u.name, ' ', u.surname) LIKE ? OR u.name LIKE ? OR u.surname LIKE ?)
        ORDER BY u.surname, u.name
        LIMIT 15
    ");
    
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode(['success' => true, 'students' => $students]);
}

function getAvailableTheses($login, $professor_id) {
    $query = $login->prepare("
        SELECT 
            d.id_diplwm as id, 
            d.title,
            d.description
        FROM diplwmatiki d
        WHERE d.professor = ? 
        AND d.status = 'Ypo Anathesi' 
        AND d.student IS NULL
        ORDER BY d.id_diplwm DESC
    ");
    
    $query->bind_param("i", $professor_id);
    $query->execute();
    $result = $query->get_result();
    
    $theses = [];
    while ($row = $result->fetch_assoc()) {
        $theses[] = $row;
    }
    
    echo json_encode(['success' => true, 'theses' => $theses]);
}
?>