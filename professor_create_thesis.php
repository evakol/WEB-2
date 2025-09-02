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

// Παίρνουμε τα δεδομένα από το POST
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';

if (empty($title) || empty($description)) {
    echo json_encode(['error' => 'Title and description are required']);
    exit;
}

// Χειρισμός του PDF αρχείου
$pdf_data = null;
if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    $pdf_data = file_get_contents($_FILES['pdf_file']['tmp_name']);
    
    // Έλεγχος αν είναι PDF
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($file_info, $pdf_data);
    finfo_close($file_info);
    
    if ($mime_type !== 'application/pdf') {
        echo json_encode(['error' => 'Only PDF files are allowed']);
        exit;
    }
} else {
    // Δημιουργούμε ένα placeholder PDF
    $pdf_data = 'placeholder_pdf_data';
}

// Εισάγουμε τη νέα διπλωματική
$insert_query = "
    INSERT INTO diplwmatiki 
    (title, description, descr_file, professor, status) 
    VALUES (?, ?, ?, ?, 'Ypo Anathesi')
";

$stmt = $login->prepare($insert_query);
$stmt->bind_param("sssi", $title, $description, $pdf_data, $professor_id);

if ($stmt->execute()) {
    $new_thesis_id = $login->insert_id;
    echo json_encode([
        'success' => true, 
        'message' => 'Η διπλωματική δημιουργήθηκε επιτυχώς',
        'thesis_id' => $new_thesis_id
    ]);
} else {
    echo json_encode(['error' => 'Failed to create thesis: ' . $login->error]);
}
?>