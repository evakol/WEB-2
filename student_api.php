<?php
session_start();
require 'connect.php';

header('Content-Type: application/json; charset=utf-8');

// Έλεγχος εξουσιοδότησης
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Βρίσκουμε το student ID
$student_query = $login->prepare("SELECT ID, AM FROM students WHERE user_ID = ?");
$student_query->bind_param("i", $user_id);
$student_query->execute();
$student_result = $student_query->get_result();

if ($student_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$student_data = $student_result->fetch_assoc();
$student_id = $student_data['ID'];

try {
    switch ($action) {
        case 'getThesis':
            getThesis($login, $student_id);
            break;
        case 'getProfile':
            getProfile($login, $user_id);
            break;
        case 'updateProfile':
            updateProfile($login, $user_id);
            break;
        case 'getManageOptions':
            getManageOptions($login, $student_id);
            break;
        case 'inviteProfessors':
            inviteProfessors($login, $student_id);
            break;
        case 'uploadFile':
            uploadFile($login, $student_id);
            break;
        case 'updateThesisDetails':
            updateThesisDetails($login, $student_id);
            break;
        case 'getExamRecord':
            getExamRecord($login, $student_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// Λήψη στοιχείων διπλωματικής
function getThesis($login, $student_id) {
    $query = "
        SELECT 
            d.id_diplwm,
            d.title,
            d.description,
            d.status,
            d.starting_date,
            d.present_date,
            d.present_time,
            d.present_venue,
            d.lib_link,
            -- Επιβλέπων
            CONCAT(up.name, ' ', up.surname) as supervisor_name,
            -- Εξεταστές
            CONCAT(ue1.name, ' ', ue1.surname) as examiner1_name,
            CONCAT(ue2.name, ' ', ue2.surname) as examiner2_name,
            -- Βαθμός
            g.final_grade
        FROM diplwmatiki d
        LEFT JOIN professors prof ON d.professor = prof.ID
        LEFT JOIN users up ON prof.user_ID = up.ID
        LEFT JOIN professors p1 ON d.exam_1 = p1.ID
        LEFT JOIN users ue1 ON p1.user_ID = ue1.ID
        LEFT JOIN professors p2 ON d.exam_2 = p2.ID
        LEFT JOIN users ue2 ON p2.user_ID = ue2.ID
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE d.student = ?
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => true, 'thesis' => null]);
        return;
    }
    
    $thesis = $result->fetch_assoc();
    $thesis['status'] = trim($thesis['status']);
    
    // Υπολογισμός χρόνου από ανάθεση
    $time_passed = null;
    if ($thesis['starting_date']) {
        $start = new DateTime($thesis['starting_date']);
        $now = new DateTime();
        $diff = $start->diff($now);
        $time_passed = [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'total_days' => $diff->days
        ];
    }
    
    echo json_encode([
        'success' => true,
        'thesis' => $thesis,
        'time_passed' => $time_passed
    ]);
}

// Λήψη προφίλ φοιτητή
function getProfile($login, $user_id) {
    $query = "
        SELECT 
            u.name,
            u.surname,
            s.AM,
            s.street,
            s.street_num,
            s.city,
            s.postcode,
            s.email,
            s.mobile_phone,
            s.landline_phone
        FROM users u
        JOIN students s ON u.ID = s.user_ID
        WHERE u.ID = ?
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
        return;
    }
    
    $profile = $result->fetch_assoc();
    echo json_encode(['success' => true, 'profile' => $profile]);
}

// Ενημέρωση προφίλ
function updateProfile($login, $user_id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    // Validation
    $required = ['street', 'street_num', 'city', 'postcode', 'email', 'mobile_phone'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Το πεδίο $field είναι υποχρεωτικό"]);
            return;
        }
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρη διεύθυνση email']);
        return;
    }
    
    $query = "
        UPDATE students 
        SET street = ?, street_num = ?, city = ?, postcode = ?, 
            email = ?, mobile_phone = ?, landline_phone = ?
        WHERE user_ID = ?
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param(
        "sisssssi",
        $data['street'],
        $data['street_num'],
        $data['city'],
        $data['postcode'],
        $data['email'],
        $data['mobile_phone'],
        $data['landline_phone'] ?? null,
        $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Τα στοιχεία ενημερώθηκαν επιτυχώς']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα ενημέρωσης']);
    }
}

// Λήψη επιλογών διαχείρισης
function getManageOptions($login, $student_id) {
    // Βρίσκουμε τη διπλωματική του φοιτητή
    $thesis_query = "
        SELECT id_diplwm, title, status, present_date, present_time, present_venue, lib_link
        FROM diplwmatiki 
        WHERE student = ?
    ";
    
    $stmt = $login->prepare($thesis_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => true, 'thesis' => null]);
        return;
    }
    
    $thesis = $result->fetch_assoc();
    $thesis['status'] = trim($thesis['status']);
    
    $response = ['success' => true, 'thesis' => $thesis];
    
    // Αν είναι υπό ανάθεση, παίρνουμε διαθέσιμους καθηγητές
    if ($thesis['status'] === 'Ypo Anathesi') {
        $prof_query = "
            SELECT p.ID, CONCAT(u.name, ' ', u.surname) as name, u.surname
            FROM professors p
            JOIN users u ON p.user_ID = u.ID
            WHERE p.ID != (SELECT professor FROM diplwmatiki WHERE id_diplwm = ?)
            ORDER BY u.surname, u.name
        ";
        
        $prof_stmt = $login->prepare($prof_query);
        $prof_stmt->bind_param("i", $thesis['id_diplwm']);
        $prof_stmt->execute();
        $prof_result = $prof_stmt->get_result();
        
        $professors = [];
        while ($row = $prof_result->fetch_assoc()) {
            $professors[] = $row;
        }
        
        $response['available_professors'] = $professors;
    }
    
    echo json_encode($response);
}

// Πρόσκληση καθηγητών στην τριμελή
function inviteProfessors($login, $student_id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['professor1']) || !isset($data['professor2'])) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        return;
    }
    
    // Βρίσκουμε τη διπλωματική
    $thesis_query = $login->prepare("SELECT id_diplwm FROM diplwmatiki WHERE student = ? AND status = 'Ypo Anathesi'");
    $thesis_query->bind_param("i", $student_id);
    $thesis_query->execute();
    $thesis_result = $thesis_query->get_result();
    
    if ($thesis_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε διπλωματική υπό ανάθεση']);
        return;
    }
    
    $thesis_data = $thesis_result->fetch_assoc();
    $thesis_id = $thesis_data['id_diplwm'];
    
    $login->autocommit(FALSE);
    
    try {
        // Εισαγωγή προσκλήσεων στον πίνακα examiners
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
        echo json_encode(['success' => true, 'message' => 'Οι προσκλήσεις στάλθηκαν επιτυχώς']);
        
    } catch (Exception $e) {
        $login->rollback();
        echo json_encode(['success' => false, 'message' => 'Σφάλμα αποστολής προσκλήσεων']);
    } finally {
        $login->autocommit(TRUE);
    }
}

// Upload αρχείου διπλωματικής
function uploadFile($login, $student_id) {
    if (!isset($_FILES['thesis_file']) || $_FILES['thesis_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα ανεβάσματος αρχείου']);
        return;
    }
    
    $file = $_FILES['thesis_file'];
    
    // Validation
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Επιτρέπονται μόνο αρχεία PDF, DOC, DOCX']);
        return;
    }
    
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Το αρχείο δεν μπορεί να είναι μεγαλύτερο από 10MB']);
        return;
    }
    
    // Βρίσκουμε τη διπλωματική υπό εξέταση
    $thesis_query = $login->prepare("SELECT id_diplwm FROM diplwmatiki WHERE student = ? AND status = 'Ypo Eksetasi'");
    $thesis_query->bind_param("i", $student_id);
    $thesis_query->execute();
    $thesis_result = $thesis_query->get_result();
    
    if ($thesis_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε διπλωματική υπό εξέταση']);
        return;
    }
    
    $thesis_data = $thesis_result->fetch_assoc();
    $thesis_id = $thesis_data['id_diplwm'];
    
    // Ανάγνωση αρχείου
    $file_content = file_get_contents($file['tmp_name']);
    
    // Ενημέρωση βάσης
    $update_query = $login->prepare("UPDATE diplwmatiki SET st_file = ? WHERE id_diplwm = ?");
    $update_query->bind_param("bi", $file_content, $thesis_id);
    
    if ($update_query->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Το αρχείο ανέβηκε επιτυχώς',
            'filename' => $file['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα αποθήκευσης αρχείου']);
    }
}

// Ενημέρωση στοιχείων διπλωματικής
function updateThesisDetails($login, $student_id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    // Βρίσκουμε τη διπλωματική υπό εξέταση
    $thesis_query = $login->prepare("SELECT id_diplwm FROM diplwmatiki WHERE student = ? AND status = 'Ypo Eksetasi'");
    $thesis_query->bind_param("i", $student_id);
    $thesis_query->execute();
    $thesis_result = $thesis_query->get_result();
    
    if ($thesis_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε διπλωματική υπό εξέταση']);
        return;
    }
    
    $thesis_data = $thesis_result->fetch_assoc();
    $thesis_id = $thesis_data['id_diplwm'];
    
    // Validation για URL
    if (!empty($data['lib_link']) && !filter_var($data['lib_link'], FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Μη έγκυρος σύνδεσμος']);
        return;
    }
    
    $query = "
        UPDATE diplwmatiki 
        SET present_date = ?, present_time = ?, present_venue = ?, lib_link = ?
        WHERE id_diplwm = ?
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param(
        "ssssi",
        $data['present_date'] ?: null,
        $data['present_time'] ?: null,
        $data['present_venue'] ?: null,
        $data['lib_link'] ?: null,
        $thesis_id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Τα στοιχεία ενημερώθηκαν επιτυχώς']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Σφάλμα ενημέρωσης']);
    }
}

// Παραγωγή πρακτικού εξέτασης σε HTML
function getExamRecord($login, $student_id) {
    $query = "
        SELECT 
            d.title,
            d.description,
            d.present_date,
            d.present_time,
            d.present_venue,
            d.app_num,
            d.app_year,
            s.AM,
            CONCAT(us.name, ' ', us.surname) as student_name,
            CONCAT(up.name, ' ', up.surname) as supervisor_name,
            CONCAT(ue1.name, ' ', ue1.surname) as examiner1_name,
            CONCAT(ue2.name, ' ', ue2.surname) as examiner2_name,
            g.final_grade,
            g.grade1_1, g.grade1_2, g.grade1_3, g.grade1_4,
            g.grade2_1, g.grade2_2, g.grade2_3, g.grade2_4,
            g.grade3_1, g.grade3_2, g.grade3_3, g.grade3_4
        FROM diplwmatiki d
        JOIN students s ON d.student = s.ID
        JOIN users us ON s.user_ID = us.ID
        LEFT JOIN professors prof ON d.professor = prof.ID
        LEFT JOIN users up ON prof.user_ID = up.ID
        LEFT JOIN professors p1 ON d.exam_1 = p1.ID
        LEFT JOIN users ue1 ON p1.user_ID = ue1.ID
        LEFT JOIN professors p2 ON d.exam_2 = p2.ID
        LEFT JOIN users ue2 ON p2.user_ID = ue2.ID
        LEFT JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE d.student = ? AND d.status = 'Peratomeni'
    ";
    
    $stmt = $login->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε πρακτικό εξέτασης']);
        return;
    }
    
    $data = $result->fetch_assoc();
    
    // Δημιουργία HTML πρακτικού
    $html = "
    <!DOCTYPE html>
    <html lang='el'>
    <head>
        <meta charset='UTF-8'>
        <title>Πρακτικό Εξέτασης - {$data['student_name']}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .info-table th, .info-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            .info-table th { background-color: #f2f2f2; font-weight: bold; }
            .grade-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .grade-table th, .grade-table td { border: 1px solid #333; padding: 10px; text-align: center; }
            .grade-table th { background-color: #e9ecef; }
            .final-grade { font-size: 18px; font-weight: bold; color: #2c3e50; text-align: center; margin: 20px 0; }
            .signatures { margin-top: 40px; }
            .signature { display: inline-block; width: 30%; text-align: center; margin: 10px; }
            @media print { body { margin: 0; } }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>ΠΑΝΕΠΙΣΤΗΜΙΟ ΠΑΤΡΩΝ</h1>
            <h2>ΤΜΗΜΑ ΜΗΧΑΝΙΚΩΝ Η/Υ & ΠΛΗΡΟΦΟΡΙΚΗΣ</h2>
            <h3>ΠΡΑΚΤΙΚΟ ΕΞΕΤΑΣΗΣ ΔΙΠΛΩΜΑΤΙΚΗΣ ΕΡΓΑΣΙΑΣ</h3>
        </div>
        
        <table class='info-table'>
            <tr><th>Τίτλος Διπλωματικής:</th><td>{$data['title']}</td></tr>
            <tr><th>Φοιτητής/τρια:</th><td>{$data['student_name']}</td></tr>
            <tr><th>Αριθμός Μητρώου:</th><td>{$data['AM']}</td></tr>
            <tr><th>Ημερομηνία Εξέτασης:</th><td>" . ($data['present_date'] ?: 'Δεν έχει καθοριστεί') . "</td></tr>
            <tr><th>Ώρα Εξέτασης:</th><td>" . ($data['present_time'] ?: 'Δεν έχει καθοριστεί') . "</td></tr>
            <tr><th>Τόπος Εξέτασης:</th><td>" . ($data['present_venue'] ?: 'Δεν έχει καθοριστεί') . "</td></tr>
            <tr><th>ΑΠ Γενικής Συνέλευσης:</th><td>{$data['app_num']}/{$data['app_year']}</td></tr>
        </table>
        
        <h3>ΤΡΙΜΕΛΗΣ ΕΠΙΤΡΟΠΗ</h3>
        <table class='info-table'>
            <tr><th>Επιβλέπων:</th><td>{$data['supervisor_name']}</td></tr>
            <tr><th>Μέλος 1:</th><td>" . ($data['examiner1_name'] ?: 'Δεν έχει οριστεί') . "</td></tr>
            <tr><th>Μέλος 2:</th><td>" . ($data['examiner2_name'] ?: 'Δεν έχει οριστεί') . "</td></tr>
        </table>";
    
    if ($data['final_grade']) {
        $html .= "
        <h3>ΒΑΘΜΟΛΟΓΙΑ</h3>
        <table class='grade-table'>
            <tr>
                <th>Κριτήριο</th>
                <th>Επιβλέπων</th>
                <th>Μέλος 1</th>
                <th>Μέλος 2</th>
            </tr>
            <tr>
                <td><strong>Περιεχόμενο (60%)</strong></td>
                <td>{$data['grade1_1']}</td>
                <td>{$data['grade2_1']}</td>
                <td>{$data['grade3_1']}</td>
            </tr>
            <tr>
                <td><strong>Παρουσίαση (15%)</strong></td>
                <td>{$data['grade1_2']}</td>
                <td>{$data['grade2_2']}</td>
                <td>{$data['grade3_2']}</td>
            </tr>
            <tr>
                <td><strong>Συγγραφή (15%)</strong></td>
                <td>{$data['grade1_3']}</td>
                <td>{$data['grade2_3']}</td>
                <td>{$data['grade3_3']}</td>
            </tr>
            <tr>
                <td><strong>Δυσκολία (10%)</strong></td>
                <td>{$data['grade1_4']}</td>
                <td>{$data['grade2_4']}</td>
                <td>{$data['grade3_4']}</td>
            </tr>
        </table>
        
        <div class='final-grade'>
            <p>ΤΕΛΙΚΟΣ ΒΑΘΜΟΣ: {$data['final_grade']}/10</p>
        </div>";
    }
    
    $html .= "
        <div class='signatures'>
            <div class='signature'>
                <p>_____________________</p>
                <p><strong>Επιβλέπων</strong></p>
                <p>{$data['supervisor_name']}</p>
            </div>
            <div class='signature'>
                <p>_____________________</p>
                <p><strong>Μέλος Επιτροπής</strong></p>
                <p>" . ($data['examiner1_name'] ?: '') . "</p>
            </div>
            <div class='signature'>
                <p>_____________________</p>
                <p><strong>Μέλος Επιτροπής</strong></p>
                <p>" . ($data['examiner2_name'] ?: '') . "</p>
            </div>
        </div>
        
        <div style='margin-top: 40px; text-align: center; font-size: 12px; color: #666;'>
            <p>Εκτυπώθηκε στις: " . date('d/m/Y H:i') . "</p>
        </div>
    </body>
    </html>";
    
    echo json_encode(['success' => true, 'html' => $html]);
}
?>