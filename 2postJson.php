<?php
session_start();
require '../connect.php';

header('Content-Type: application/json; charset=utf-8');

// Έλεγχος εξουσιοδότησης
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Ανάγνωση JSON δεδομένων
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

try {
    $login->autocommit(FALSE); // Ξεκινάμε transaction
    
    $professors_imported = 0;
    $students_imported = 0;
    
    // Εισαγωγή καθηγητών
    if (isset($data['professors']) && is_array($data['professors'])) {
        foreach ($data['professors'] as $prof) {
            // Δημιουργία username από email
            $username = explode('@', $prof['email'])[0];
            $password = 'prof_' . rand(1000, 9999); // Τυχαίος κωδικός
            
            // Εισαγωγή στον πίνακα users
            $stmt = $login->prepare("INSERT INTO users (username, password, name, surname, role) VALUES (?, ?, ?, ?, 'Professor')");
            $stmt->bind_param("ssss", $username, $password, $prof['name'], $prof['surname']);
            
            if ($stmt->execute()) {
                $user_id = $login->insert_id;
                
                // Εισαγωγή στον πίνακα professors
                $office_num = isset($prof['office']) ? $prof['office'] : rand(100, 500);
                $phone = isset($prof['phone']) ? $prof['phone'] : 2610000000 + rand(1000, 9999);
                
                $prof_stmt = $login->prepare("INSERT INTO professors (user_ID, office_num, email, phone) VALUES (?, ?, ?, ?)");
                $prof_stmt->bind_param("iisi", $user_id, $office_num, $prof['email'], $phone);
                
                if ($prof_stmt->execute()) {
                    $professors_imported++;
                }
            }
        }
    }
    
    // Εισαγωγή φοιτητών
    if (isset($data['students']) && is_array($data['students'])) {
        foreach ($data['students'] as $student) {
            // Δημιουργία username από email
            $username = explode('@', $student['email'])[0];
            $password = 'stud_' . rand(1000, 9999); // Τυχαίος κωδικός
            
            // Εισαγωγή στον πίνακα users
            $stmt = $login->prepare("INSERT INTO users (username, password, name, surname, role) VALUES (?, ?, ?, ?, 'Student')");
            $stmt->bind_param("ssss", $username, $password, $student['name'], $student['surname']);
            
            if ($stmt->execute()) {
                $user_id = $login->insert_id;
                
                // Δημιουργία ΑΜ αν δεν υπάρχει
                $am = isset($student['am']) ? $student['am'] : rand(1000000, 9999999);
                
                // Εισαγωγή στον πίνακα students
                $street = isset($student['address']['street']) ? $student['address']['street'] : 'Πανεπιστημίου';
                $street_num = isset($student['address']['number']) ? $student['address']['number'] : rand(1, 100);
                $city = isset($student['address']['city']) ? $student['address']['city'] : 'Πάτρα';
                $postcode = isset($student['address']['postcode']) ? $student['address']['postcode'] : 26100;
                $mobile = isset($student['phone']) ? $student['phone'] : 6900000000 + rand(10000000, 99999999);
                $landline = isset($student['landline']) ? $student['landline'] : 2610000000 + rand(100000, 999999);
                
                $stud_stmt = $login->prepare("INSERT INTO students (user_ID, AM, street, street_num, city, postcode, email, mobile_phone, landline_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stud_stmt->bind_param("isssissii", $user_id, $am, $street, $street_num, $city, $postcode, $student['email'], $mobile, $landline);
                
                if ($stud_stmt->execute()) {
                    $students_imported++;
                }
            }
        }
    }
    
    $login->commit(); // Αποδέχονται οι αλλαγές
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'professors_imported' => $professors_imported,
            'students_imported' => $students_imported
        ]
    ]);
    
} catch (Exception $e) {
    $login->rollback(); // Ακύρωση αλλαγών σε περίπτωση σφάλματος
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    $login->autocommit(TRUE); // Επαναφορά autocommit
}
?>