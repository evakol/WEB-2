<?php
session_start();
require '../connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    echo json_encode(['success' => false, 'message' => 'Μη εξουσιοδοτημένη πρόσβαση']);
    exit;
}

$user_id = $_SESSION['user_id'];
$prof_query = $login->prepare("SELECT ID FROM professors WHERE user_ID = ?");
$prof_query->bind_param("i", $user_id);
$prof_query->execute();
$professor_id = $prof_query->get_result()->fetch_assoc()['ID'];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_dashboard_summary':
        getDashboardSummary($login, $professor_id);
        break;
    case 'get_completion_stats':
        getCompletionStats($login, $professor_id);
        break;
    case 'get_grade_stats':
        getGradeStats($login, $professor_id);
        break;
    case 'get_thesis_count_stats':
        getThesisCountStats($login, $professor_id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Άγνωστη ενέργεια']);
        break;
}

function getDashboardSummary($login, $professor_id) {
    $summary_query = $login->prepare("
        SELECT 
            COUNT(CASE WHEN d.professor = ? THEN 1 END) as total_supervised,
            COUNT(CASE WHEN (d.exam_1 = ? OR d.exam_2 = ?) THEN 1 END) as total_examined,
            COUNT(CASE WHEN d.status = 'Energi' AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?) THEN 1 END) as active_theses,
            COUNT(CASE WHEN d.status = 'Ypo Eksetasi' AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?) THEN 1 END) as pending_grading,
            COUNT(CASE WHEN d.status = 'Ypo Anathesi' AND d.professor = ? THEN 1 END) as available_for_assignment
        FROM diplwmatiki d
        WHERE d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?
    ");
    
    $summary_query->bind_param("iiiiiiiiiiiii", 
        $professor_id, $professor_id, $professor_id,
        $professor_id, $professor_id, $professor_id,
        $professor_id, $professor_id, $professor_id,
        $professor_id, $professor_id, $professor_id, $professor_id
    );
    $summary_query->execute();
    $summary = $summary_query->get_result()->fetch_assoc();
    
    $invitations_query = $login->prepare("
        SELECT COUNT(*) as pending_invitations
        FROM examiners e
        WHERE e.exam_id = ? AND e.status = 'Energi'
    ");
    $invitations_query->bind_param("i", $professor_id);
    $invitations_query->execute();
    $invitations = $invitations_query->get_result()->fetch_assoc();
    
    $summary['pending_invitations'] = $invitations['pending_invitations'];
    
    echo json_encode(['success' => true, 'summary' => $summary]);
}

function getCompletionStats($login, $professor_id) {
    // Ως επιβλέπων
    $supervisor_query = $login->prepare("
        SELECT 
            AVG(DATEDIFF(COALESCE(d.present_date, CURDATE()), d.starting_date)) as avg_completion_days,
            COUNT(*) as total_supervised
        FROM diplwmatiki d
        WHERE d.professor = ? 
        AND d.status IN ('Peratomeni', 'Ypo Eksetasi')
        AND d.starting_date IS NOT NULL
    ");
    $supervisor_query->bind_param("i", $professor_id);
    $supervisor_query->execute();
    $supervisor_stats = $supervisor_query->get_result()->fetch_assoc();
    
    // Ως εξεταστής
    $examiner_query = $login->prepare("
        SELECT 
            AVG(DATEDIFF(COALESCE(d.present_date, CURDATE()), d.starting_date)) as avg_completion_days,
            COUNT(*) as total_examined
        FROM diplwmatiki d
        WHERE (d.exam_1 = ? OR d.exam_2 = ?)
        AND d.status IN ('Peratomeni', 'Ypo Eksetasi')
        AND d.starting_date IS NOT NULL
    ");
    $examiner_query->bind_param("ii", $professor_id, $professor_id);
    $examiner_query->execute();
    $examiner_stats = $examiner_query->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'supervisor' => [
            'avg_completion_months' => $supervisor_stats['avg_completion_days'] ? round($supervisor_stats['avg_completion_days'] / 30, 1) : 0,
            'total_supervised' => $supervisor_stats['total_supervised']
        ],
        'examiner' => [
            'avg_completion_months' => $examiner_stats['avg_completion_days'] ? round($examiner_stats['avg_completion_days'] / 30, 1) : 0,
            'total_examined' => $examiner_stats['total_examined']
        ]
    ]);
}

function getGradeStats($login, $professor_id) {
    // Μέσος βαθμός ως επιβλέπων
    $supervisor_grades = $login->prepare("
        SELECT 
            AVG(g.final_grade) as avg_grade,
            COUNT(*) as total_graded
        FROM diplwmatiki d
        JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE d.professor = ? AND d.status = 'Peratomeni'
    ");
    $supervisor_grades->bind_param("i", $professor_id);
    $supervisor_grades->execute();
    $supervisor_grade_stats = $supervisor_grades->get_result()->fetch_assoc();
    
    // Μέσος βαθμός ως εξεταστής
    $examiner_grades = $login->prepare("
        SELECT 
            AVG(g.final_grade) as avg_grade,
            COUNT(*) as total_graded
        FROM diplwmatiki d
        JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE (d.exam_1 = ? OR d.exam_2 = ?) AND d.status = 'Peratomeni'
    ");
    $examiner_grades->bind_param("ii", $professor_id, $professor_id);
    $examiner_grades->execute();
    $examiner_grade_stats = $examiner_grades->get_result()->fetch_assoc();
    
    // Κατανομή βαθμών
    $distribution_query = $login->prepare("
        SELECT 
            CASE 
                WHEN g.final_grade >= 8.5 THEN 'Άριστα (8.5-10)'
                WHEN g.final_grade >= 6.5 THEN 'Καλώς (6.5-8.5)'
                WHEN g.final_grade >= 5.0 THEN 'Μετρίως (5.0-6.5)'
                ELSE 'Ανεπιτυχώς (<5.0)'
            END as grade_category,
            COUNT(*) as count
        FROM diplwmatiki d
        JOIN grades g ON d.id_diplwm = g.diplwm_id
        WHERE (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?)
        AND d.status = 'Peratomeni'
        GROUP BY 
            CASE 
                WHEN g.final_grade >= 8.5 THEN 'Άριστα (8.5-10)'
                WHEN g.final_grade >= 6.5 THEN 'Καλώς (6.5-8.5)'
                WHEN g.final_grade >= 5.0 THEN 'Μετρίως (5.0-6.5)'
                ELSE 'Ανεπιτυχώς (<5.0)'
            END
        ORDER BY MIN(g.final_grade) DESC
    ");
    $distribution_query->bind_param("iii", $professor_id, $professor_id, $professor_id);
    $distribution_query->execute();
    $distribution_result = $distribution_query->get_result();
    
    $grade_distribution = [];
    while ($row = $distribution_result->fetch_assoc()) {
        $grade_distribution[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'supervisor' => [
            'avg_grade' => round($supervisor_grade_stats['avg_grade'] ?? 0, 2),
            'total_graded' => $supervisor_grade_stats['total_graded']
        ],
        'examiner' => [
            'avg_grade' => round($examiner_grade_stats['avg_grade'] ?? 0, 2),
            'total_graded' => $examiner_grade_stats['total_graded']
        ],
        'distribution' => $grade_distribution
    ]);
}

function getThesisCountStats($login, $professor_id) {
    $counts_query = $login->prepare("
        SELECT 
            SUM(CASE WHEN d.professor = ? THEN 1 ELSE 0 END) as supervised_total,
            SUM(CASE WHEN (d.exam_1 = ? OR d.exam_2 = ?) THEN 1 ELSE 0 END) as examined_total,
            SUM(CASE WHEN d.status = 'Ypo Anathesi' AND d.professor = ? THEN 1 ELSE 0 END) as pending_assignment,
            SUM(CASE WHEN d.status = 'Energi' AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?) THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN d.status = 'Ypo Eksetasi' AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?) THEN 1 ELSE 0 END) as under_examination,
            SUM(CASE WHEN d.status = 'Peratomeni' AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?) THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN d.status = 'Akyromeni' AND (d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?) THEN 1 ELSE 0 END) as cancelled
        FROM diplwmatiki d
        WHERE d.professor = ? OR d.exam_1 = ? OR d.exam_2 = ?
    ");
    
    $counts_query->bind_param("iiiiiiiiiiiiiiiiii", 
        $professor_id, $professor_id, $professor_id, $professor_id,
        $professor_id, $professor_id, $professor_id,
        $professor_id, $professor_id, $professor_id,
        $professor_id, $professor_id, $professor_id,
        $professor_id, $professor_id, $professor_id,
        $professor_id, $professor_id
    );
    $counts_query->execute();
    $counts = $counts_query->get_result()->fetch_assoc();
    
    echo json_encode(['success' => true, 'totals' => $counts]);
}
?>