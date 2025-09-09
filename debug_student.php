<?php
session_start();
require 'connect.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Debug Student System</h2>";

// Έλεγχος session
echo "<h3>Session Info:</h3>";
if (isset($_SESSION['role'])) {
    echo "✅ Role: " . $_SESSION['role'] . "<br>";
    echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ No session found<br>";
    echo "<a href='login.html'>Login first</a><br>";
    exit;
}

// Έλεγχος σύνδεσης βάσης
echo "<h3>Database Connection:</h3>";
if ($login->connect_error) {
    echo "❌ Database connection failed: " . $login->connect_error . "<br>";
    exit;
} else {
    echo "✅ Database connected successfully<br>";
}

// Έλεγχος student record
echo "<h3>Student Record:</h3>";
$user_id = $_SESSION['user_id'];
$student_query = $login->prepare("SELECT ID, AM FROM students WHERE user_ID = ?");
$student_query->bind_param("i", $user_id);
$student_query->execute();
$student_result = $student_query->get_result();

if ($student_result->num_rows === 0) {
    echo "❌ No student record found for user_id: $user_id<br>";
    
    // Δείχνουμε τι υπάρχει στη βάση
    echo "<h4>Available students:</h4>";
    $all_students = $login->query("SELECT s.ID, s.AM, s.user_ID, u.name, u.surname FROM students s JOIN users u ON s.user_ID = u.ID");
    while ($row = $all_students->fetch_assoc()) {
        echo "Student ID: {$row['ID']}, AM: {$row['AM']}, User ID: {$row['user_ID']}, Name: {$row['name']} {$row['surname']}<br>";
    }
} else {
    $student_data = $student_result->fetch_assoc();
    echo "✅ Student found - ID: {$student_data['ID']}, AM: {$student_data['AM']}<br>";
    
    // Έλεγχος διπλωματικής
    echo "<h3>Thesis Check:</h3>";
    $thesis_query = $login->prepare("SELECT id_diplwm, title, status FROM diplwmatiki WHERE student = ?");
    $thesis_query->bind_param("i", $student_data['ID']);
    $thesis_query->execute();
    $thesis_result = $thesis_query->get_result();
    
    if ($thesis_result->num_rows === 0) {
        echo "❌ No thesis found for this student<br>";
        
        // Δείχνουμε όλες τις διπλωματικές
        echo "<h4>All theses in database:</h4>";
        $all_theses = $login->query("SELECT id_diplwm, title, status, student FROM diplwmatiki");
        while ($row = $all_theses->fetch_assoc()) {
            echo "Thesis ID: {$row['id_diplwm']}, Title: {$row['title']}, Status: {$row['status']}, Student ID: {$row['student']}<br>";
        }
    } else {
        $thesis = $thesis_result->fetch_assoc();
        echo "✅ Thesis found - ID: {$thesis['id_diplwm']}, Title: {$thesis['title']}, Status: {$thesis['status']}<br>";
    }
}

echo "<h3>Test Direct API Call:</h3>";
echo "<a href='student/getThesis.php' target='_blank'>Test getThesis.php directly</a><br>";
echo "<a href='student/getProfile.php' target='_blank'>Test getProfile.php directly</a><br>";
?>
