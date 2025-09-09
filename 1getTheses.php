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

try {
    // Ερώτημα για διπλωματικές που είναι ενεργές ή υπό εξέταση
    $query = "
        SELECT 
            d.id_diplwm as ID,
            d.title,
            d.description,
            d.status,
            d.starting_date as assignment_date,
            s.AM as student_am,
            CONCAT(us.name, ' ', us.surname) as student_name,
            CONCAT(up.name, ' ', up.surname) as supervisor_name,
            up.name as supervisor_name,
            up.surname as supervisor_surname,
            CONCAT(ue1.name, ' ', ue1.surname) as committee1_name,
            ue1.name as committee1_name,
            ue1.surname as committee1_surname,
            CONCAT(ue2.name, ' ', ue2.surname) as committee2_name,
            ue2.name as committee2_name,
            ue2.surname as committee2_surname
        FROM diplwmatiki d
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN users us ON s.user_ID = us.ID
        LEFT JOIN professors p ON d.professor = p.ID
        LEFT JOIN users up ON p.user_ID = up.ID
        LEFT JOIN professors p1 ON d.exam_1 = p1.ID
        LEFT JOIN users ue1 ON p1.user_ID = ue1.ID
        LEFT JOIN professors p2 ON d.exam_2 = p2.ID
        LEFT JOIN users ue2 ON p2.user_ID = ue2.ID
        WHERE d.status IN ('Energi', 'Ypo Eksetasi')
        ORDER BY d.starting_date DESC
    ";
    
    $result = $login->query($query);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . $login->error);
    }
    
    $theses = [];
    while ($row = $result->fetch_assoc()) {
        // Μετατροπή status σε αριθμό για ευκολότερη επεξεργασία
        $status_map = [
            'Ypo Anathesi' => 1,
            'Energi' => 2,
            'Ypo Eksetasi' => 3,
            'Peratomeni' => 4,
            'Akyromeni' => 5
        ];
        
        $row['status'] = $status_map[$row['status']] ?? 0;
        $theses[] = $row;
    }
    
    echo json_encode($theses);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>