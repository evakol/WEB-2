<?php
session_start();
require '../connect.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

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

// Παίρνουμε τις ενεργές προσκλήσεις
$query = "
    SELECT 
        e.diplwm_id,
        e.invitation_date,
        e.status,
        d.title,
        d.description,
        u_supervisor.name as supervisor_name,
        u_supervisor.surname as supervisor_surname,
        s.AM as student_am,
        u_student.name as student_name,
        u_student.surname as student_surname
    FROM examiners e
    JOIN diplwmatiki d ON e.diplwm_id = d.id_diplwm
    JOIN professors p_supervisor ON d.professor = p_supervisor.ID
    JOIN users u_supervisor ON p_supervisor.user_ID = u_supervisor.ID
    LEFT JOIN students st ON d.student = st.ID
    LEFT JOIN users u_student ON st.user_ID = u_student.ID
    WHERE e.exam_id = ? AND e.status = 'Energi'
    ORDER BY e.invitation_date DESC
";

$stmt = $login->prepare($query);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();

$invitations = [];
while ($row = $result->fetch_assoc()) {
    $invitations[] = $row;
}

echo json_encode($invitations);
?>