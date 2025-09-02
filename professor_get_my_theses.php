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

// Παίρνουμε τις διπλωματικές που επιβλέπει ή είναι εξεταστής
$query = "
    SELECT DISTINCT
        d.id_diplwm,
        d.title,
        d.description,
        d.status,
        d.starting_date,
        s.AM as student_am,
        u_student.name as student_name,
        u_student.surname as student_surname,
        u_supervisor.name as supervisor_name,
        u_supervisor.surname as supervisor_surname,
        p_supervisor.ID as supervisor_id,
        CASE 
            WHEN d.professor = ? THEN 'supervisor'
            WHEN e.exam_id = ? THEN 'examiner'
            ELSE 'other'
        END as role_in_thesis,
        d.present_date,
        d.present_time,
        d.present_venue,
        g.final_grade
    FROM diplwmatiki d
    LEFT JOIN students s ON d.student = s.ID
    LEFT JOIN users u_student ON s.user_ID = u_student.ID
    LEFT JOIN professors p_supervisor ON d.professor = p_supervisor.ID
    LEFT JOIN users u_supervisor ON p_supervisor.user_ID = u_supervisor.ID
    LEFT JOIN examiners e ON d.id_diplwm = e.diplwm_id AND e.exam_id = ?
    LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
    WHERE d.professor = ? OR e.exam_id = ?
    ORDER BY d.starting_date DESC, d.id_diplwm DESC
";

$stmt = $login->prepare($query);
$stmt->bind_param("iiiii", $professor_id, $professor_id, $professor_id, $professor_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();

$theses = [];
while ($row = $result->fetch_assoc()) {
    // Μετατρέπουμε την κατάσταση σε ελληνικά
    $status_mapping = [
        'Ypo Anathesi' => 'Υπό Ανάθεση',
        'Energi' => 'Ενεργή', 
        'Ypo Eksetasi' => 'Υπό Εξέταση',
        'Peratomeni' => 'Περατωμένη',
        'Akyromeni' => 'Ακυρωμένη'
    ];
    
    $row['status_greek'] = $status_mapping[$row['status']] ?? $row['status'];
    
    // Υπολογισμός χρόνου που έχει περάσει
    if ($row['starting_date']) {
        $start_date = new DateTime($row['starting_date']);
        $now = new DateTime();
        $interval = $start_date->diff($now);
        
        if ($interval->y > 0) {
            $row['time_elapsed'] = $interval->y . ' έτος/η και ' . $interval->m . ' μήνες';
        } elseif ($interval->m > 0) {
            $row['time_elapsed'] = $interval->m . ' μήνες και ' . $interval->d . ' μέρες';
        } else {
            $row['time_elapsed'] = $interval->d . ' μέρες';
        }
    } else {
        $row['time_elapsed'] = 'Δεν έχει ξεκινήσει';
    }
    
    $theses[] = $row;
}

echo json_encode($theses);
?>