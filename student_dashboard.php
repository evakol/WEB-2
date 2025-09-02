<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') 
    {
    header("Location: login.html");
    exit;
    }



// Παίρνουμε τα στοιχεία του φοιτητή
$user_id = $_SESSION['user_id'];
$query = $login->prepare("SELECT u.name, u.surname FROM users u WHERE u.ID = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();
$student_name = $user_data['name'] . ' ' . $user_data['surname'];
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Διπλωματικές Εργασίες</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2c3e50;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo h1 {
            color: #667eea;
            font-size: 24px;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-name {
            color: #2c3e50;
            font-weight: 600;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .welcome-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 32px;
        }

        .welcome-section p {
            color: #7f8c8d;
            font-size: 18px;
            line-height: 1.6;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .card-description {
            color: #7f8c8d;
            line-height: 1.6;
            font-size: 14px;
        }

        .stats-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            background: #f8f9ff;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 600;
        }

        .footer {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            max-width: 800px;
            margin: 5% auto;
            padding: 40px;
            border-radius: 16px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 15px;
        }

        .close-btn {
            font-size: 28px;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .welcome-section {
                padding: 30px 20px;
            }
            
            .dashboard-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Σύστημα Διπλωματικών - Φοιτητών</h1>
            </div>
            <div class="user-info">
                <span class="user-name">Φοιτητής: <span id="username"><?php echo htmlspecialchars($student_name); ?></span></span>
                <a href="login.html" class="logout-btn">Αποσύνδεση</a>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="welcome-section">
            <h2>Καλώς ήρθες!</h2>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card" onclick="openModal('availableThesesModal')">
                <div class="card-title">Διαθέσιμες Διπλωματικές</div>
                <div class="card-description">Δες όλες τις διαθέσιμες διπλωματικές εργασίες και κάνε αίτηση για την επιλογή σου.</div>
            </div>

            <div class="dashboard-card" onclick="openModal('myThesisModal')">
                <div class="card-title">Η Διπλωματική μου</div>
                <div class="card-description">Παρακολούθησε την πρόοδο της διπλωματικής σου εργασίας και τις επικοινωνίες με τον επιβλέποντα.</div>
            </div>


            <div class="dashboard-card" onclick="openModal('profileModal')">
                <div class="card-title">Προφίλ & Στοιχεία</div>
                <div class="card-description">Ενημέρωσε τα προσωπικά σου στοιχεία και τις πληροφορίες επικοινωνίας.</div>
            </div>
        </div>

    </div>

    <div class="footer">
        <p>&copy; 2025 ΤΜΗΥΠ - Σύστημα Διαχείρισης Διπλωματικών Εργασιών</p>
    </div>

    <!-- Modals -->
    <div id="availableThesesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Διαθέσιμες Διπλωματικές Εργασίες</h3>
                <span class="close-btn" onclick="closeModal('availableThesesModal')">&times;</span>
            </div>
            <p>Εδώ θα εμφανίζονται όλες οι διαθέσιμες διπλωματικές εργασίες που μπορείς να επιλέξεις...</p>
        </div>
    </div>

    <div id="myThesisModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Η Διπλωματική μου Εργασία</h3>
                <span class="close-btn" onclick="closeModal('myThesisModal')">&times;</span>
            </div>
            <p>Εδώ θα βλέπεις τις λεπτομέρειες της διπλωματικής σου εργασίας και την επικοινωνία με τον επιβλέποντα...</p>
        </div>
    </div>

    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Προφίλ & Στοιχεία</h3>
                <span class="close-btn" onclick="closeModal('profileModal')">&times;</span>
            </div>
            <p>Εδώ θα μπορείς να ενημερώσεις τα στοιχεία σου...</p>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Simulate loading user data
        document.addEventListener('DOMContentLoaded', function() {
            // Here you would normally fetch user data from your backend
            console.log('Student dashboard loaded successfully');
        });
    </script>
</body>
</html>