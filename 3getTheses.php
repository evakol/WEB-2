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
    // Ερώτημα για διπλωματικές που μπορεί να διαχειριστεί η γραμματεία
    $query = "
        SELECT 
            d.id_diplwm as ID,
            d.title,
            d.status,
            d.starting_date,
            s.AM as student_am,
            CONCAT(us.name, ' ', us.surname) as student_name,
            CONCAT(up.name, ' ', up.surname) as supervisor_name
        FROM diplwmatiki d
        LEFT JOIN students s ON d.student = s.ID
        LEFT JOIN users us ON s.user_ID = us.ID
        LEFT JOIN professors p ON d.professor = p.ID
        LEFT JOIN users up ON p.user_ID = up.ID
        WHERE d.status IN ('Energi', 'Ypo Eksetasi')
        ORDER BY d.starting_date DESC
    ";
    
    $result = $login->query($query);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . $login->error);
    }
    
    $theses = [];
    while ($row = $result->fetch_assoc()) {
        // Μετατροπή status σε αριθμό
        $status_map = [
            'Energi' => 2,
            'Ypo Eksetasi' => 3
        ];
        
        $row['status'] = $status_map[$row['status']] ?? 0;
        $theses[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $theses
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>