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
    
    // Ερώτημα για περατωμένη διπλωματική με πλήρη στοιχεία
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
        echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε περατωμένη διπλωματική εργασία']);
        exit;
    }
    
    $data = $result->fetch_assoc();
    
    // Δημιουργία HTML πρακτικού εξέτασης
    $html = "
    <!DOCTYPE html>
    <html lang='el'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Πρακτικό Εξέτασης - {$data['student_name']}</title>
        <style>
            body { 
                font-family: 'Times New Roman', serif; 
                margin: 20px; 
                line-height: 1.6; 
                color: #333;
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px; 
                border-bottom: 3px solid #333; 
                padding-bottom: 20px; 
            }
            .header h1 { 
                font-size: 24px; 
                margin-bottom: 5px; 
                color: #1a472a;
            }
            .header h2 { 
                font-size: 18px; 
                margin-bottom: 5px; 
                color: #2c5530;
            }
            .header h3 { 
                font-size: 16px; 
                color: #444;
                text-decoration: underline;
            }
            .info-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0; 
                border: 2px solid #333;
            }
            .info-table th, .info-table td { 
                border: 1px solid #666; 
                padding: 12px; 
                text-align: left; 
            }
            .info-table th { 
                background-color: #f5f5f5; 
                font-weight: bold; 
                width: 30%;
            }
            .grade-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0; 
                border: 2px solid #333;
            }
            .grade-table th, .grade-table td { 
                border: 1px solid #333; 
                padding: 10px; 
                text-align: center; 
                font-weight: bold;
            }
            .grade-table th { 
                background-color: #e9ecef; 
                font-size: 14px;
            }
            .grade-table td {
                font-size: 16px;
            }
            .final-grade { 
                font-size: 24px; 
                font-weight: bold; 
                color: #1a472a; 
                text-align: center; 
                margin: 30px 0; 
                padding: 20px;
                border: 3px solid #1a472a;
                background-color: #f0f8f0;
            }
            .signatures { 
                margin-top: 50px; 
                page-break-inside: avoid;
            }
            .signature { 
                display: inline-block; 
                width: 30%; 
                text-align: center; 
                margin: 20px 1.5%; 
                vertical-align: top;
            }
            .signature-line {
                border-bottom: 2px solid #333;
                height: 40px;
                margin-bottom: 10px;
            }
            .signature p {
                margin: 5px 0;
                font-weight: bold;
            }
            .committee-title {
                color: #1a472a;
                font-size: 18px;
                margin: 25px 0 15px 0;
                text-align: center;
                text-decoration: underline;
            }
            .grades-title {
                color: #1a472a;
                font-size: 18px;
                margin: 25px 0 15px 0;
                text-align: center;
                text-decoration: underline;
            }
            .footer-info {
                margin-top: 40px; 
                text-align: center; 
                font-size: 12px; 
                color: #666;
                border-top: 1px solid #ccc;
                padding-top: 20px;
            }
            @media print { 
                body { margin: 15px; }
                .header { border-bottom: 2px solid #000; }
                .signatures { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>ΠΑΝΕΠΙΣΤΗΜΙΟ ΠΑΤΡΩΝ</h1>
            <h2>ΤΜΗΜΑ ΜΗΧΑΝΙΚΩΝ Η/Υ & ΠΛΗΡΟΦΟΡΙΚΗΣ</h2>
            <h3>ΠΡΑΚΤΙΚΟ ΕΞΕΤΑΣΗΣ ΔΙΠΛΩΜΑΤΙΚΗΣ ΕΡΓΑΣΙΑΣ</h3>
        </div>
        
        <table class='info-table'>
            <tr><th>Τίτλος Διπλωματικής Εργασίας:</th><td><strong>{$data['title']}</strong></td></tr>
            <tr><th>Φοιτητής/τρια:</th><td><strong>{$data['student_name']}</strong></td></tr>
            <tr><th>Αριθμός Μητρώου:</th><td><strong>{$data['AM']}</strong></td></tr>
            <tr><th>Ημερομηνία Εξέτασης:</th><td>" . ($data['present_date'] ? date('d/m/Y', strtotime($data['present_date'])) : 'Δεν έχει καθοριστεί') . "</td></tr>
            <tr><th>Ώρα Εξέτασης:</th><td>" . ($data['present_time'] ? date('H:i', strtotime($data['present_time'])) : 'Δεν έχει καθοριστεί') . "</td></tr>
            <tr><th>Τόπος Εξέτασης:</th><td>" . ($data['present_venue'] ?: 'Δεν έχει καθοριστεί') . "</td></tr>
            <tr><th>ΑΠ Γενικής Συνέλευσης:</th><td><strong>{$data['app_num']}/{$data['app_year']}</strong></td></tr>
        </table>
        
        <h3 class='committee-title'>ΤΡΙΜΕΛΗΣ ΕΠΙΤΡΟΠΗ ΕΞΕΤΑΣΗΣ</h3>
        <table class='info-table'>
            <tr><th>Επιβλέπων Καθηγητής:</th><td><strong>{$data['supervisor_name']}</strong></td></tr>
            <tr><th>Μέλος Επιτροπής 1:</th><td><strong>" . ($data['examiner1_name'] ?: 'Δεν έχει οριστεί') . "</strong></td></tr>
            <tr><th>Μέλος Επιτροπής 2:</th><td><strong>" . ($data['examiner2_name'] ?: 'Δεν έχει οριστεί') . "</strong></td></tr>
        </table>";
    
    // Προσθήκη βαθμολογίας αν υπάρχει
    if ($data['final_grade']) {
        $html .= "
        <h3 class='grades-title'>ΑΝΑΛΥΤΙΚΗ ΒΑΘΜΟΛΟΓΙΑ</h3>
        <table class='grade-table'>
            <thead>
                <tr>
                    <th>Κριτήριο Αξιολόγησης</th>
                    <th>Επιβλέπων<br>({$data['supervisor_name']})</th>
                    <th>Μέλος 1<br>(" . ($data['examiner1_name'] ?: 'N/A') . ")</th>
                    <th>Μέλος 2<br>(" . ($data['examiner2_name'] ?: 'N/A') . ")</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Περιεχόμενο Εργασίας (60%)</strong></td>
                    <td>{$data['grade1_1']}/10</td>
                    <td>{$data['grade2_1']}/10</td>
                    <td>{$data['grade3_1']}/10</td>
                </tr>
                <tr>
                    <td><strong>Παρουσίαση Εργασίας (15%)</strong></td>
                    <td>{$data['grade1_2']}/10</td>
                    <td>{$data['grade2_2']}/10</td>
                    <td>{$data['grade3_2']}/10</td>
                </tr>
                <tr>
                    <td><strong>Συγγραφή Εργασίας (15%)</strong></td>
                    <td>{$data['grade1_3']}/10</td>
                    <td>{$data['grade2_3']}/10</td>
                    <td>{$data['grade3_3']}/10</td>
                </tr>
                <tr>
                    <td><strong>Βαθμός Δυσκολίας (10%)</strong></td>
                    <td>{$data['grade1_4']}/10</td>
                    <td>{$data['grade2_4']}/10</td>
                    <td>{$data['grade3_4']}/10</td>
                </tr>
            </tbody>
        </table>
        
        <div class='final-grade'>
            <p>ΤΕΛΙΚΟΣ ΒΑΘΜΟΣ ΔΙΠΛΩΜΑΤΙΚΗΣ ΕΡΓΑΣΙΑΣ</p>
            <p style='font-size: 32px; margin: 10px 0;'>{$data['final_grade']}/10</p>
            <p>" . getGradeText($data['final_grade']) . "</p>
        </div>";
    }
    
    $html .= "
        <div class='signatures'>
            <div class='signature'>
                <div class='signature-line'></div>
                <p><strong>Επιβλέπων Καθηγητής</strong></p>
                <p>{$data['supervisor_name']}</p>
            </div>
            <div class='signature'>
                <div class='signature-line'></div>
                <p><strong>Μέλος Επιτροπής</strong></p>
                <p>" . ($data['examiner1_name'] ?: 'Δεν έχει οριστεί') . "</p>
            </div>
            <div class='signature'>
                <div class='signature-line'></div>
                <p><strong>Μέλος Επιτροπής</strong></p>
                <p>" . ($data['examiner2_name'] ?: 'Δεν έχει οριστεί') . "</p>
            </div>
        </div>
        
        <div class='footer-info'>
            <p><strong>Πρακτικό εκτυπώθηκε στις:</strong> " . date('d/m/Y H:i') . "</p>
            <p><strong>Τμήμα Μηχανικών Η/Υ & Πληροφορικής - Πανεπιστήμιο Πατρών</strong></p>
        </div>
    </body>
    </html>";
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Συνάρτηση για μετατροπή βαθμού σε κείμενο
function getGradeText($grade) {
    if ($grade >= 8.5) return "ΑΡΙΣΤΑ";
    if ($grade >= 6.5) return "ΛΙΑΝ ΚΑΛΩΣ";
    if ($grade >= 5.0) return "ΚΑΛΩΣ";
    return "ΑΠΟΤΥΧΙΑ";
}
?>