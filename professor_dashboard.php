<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') 
    {
    header("Location: login.html");
    exit;
    }



// Παίρνουμε τα στοιχεία του καθηγητή
$user_id = $_SESSION['user_id'];
$query = $login->prepare("SELECT u.name, u.surname FROM users u WHERE u.ID = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();
$professor_name = $user_data['name'] . ' ' . $user_data['surname'];
?>


<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard - Διπλωματικές Εργασίες</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="professor_style.css">
</head>
 <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
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
            color: #2c3e50;
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
            border-color: #3498db;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: white;
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
            color: #3498db;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 600;
        }

        .notifications {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .notification-item {
            padding: 15px;
            border-left: 4px solid #3498db;
            background: #f8f9ff;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .notification-item:last-child {
            margin-bottom: 0;
        }

        .notification-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .notification-text {
            color: #7f8c8d;
            font-size: 14px;
        }

        .notification-urgent {
            border-left-color: #e74c3c;
        }

        .notification-success {
            border-left-color: #27ae60;
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
            max-width: 900px;
            margin: 3% auto;
            padding: 40px;
            border-radius: 16px;
            max-height: 85vh;
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

<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <h1> Σύστημα Διπλωματικών - Καθηγητής</h1>
            </div>
            <div class="user-info">
                <span class="user-name">Καθηγητής: <span id="username"> <?php echo htmlspecialchars($professor_name); ?></span></span>
                <a href="login.html" class="logout-btn">Αποσύνδεση</a>
            </div>
        </div>
    </div>

    <div class="main-container">
        <!-- Dashboard Main View -->
        <div id="dashboard" class="dashboard-view">
            <div class="welcome-section">
                <h2>Καλώς ήρθατε!</h2>
                <p>Διαχειριστείτε τις διπλωματικές εργασίες, παρακολουθήστε την πρόοδο των φοιτητών σας και διαχειριστείτε τις εξεταστικές επιτροπές.</p>
            </div>

            <div class="notifications" id="notifications">
                <h3>Ειδοποιήσεις</h3>
                <div class="notification-loading">Φόρτωση ειδοποιήσεων...</div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card" onclick="showSection('myTheses')">
                    <div class="card-title">Οι Διπλωματικές μου</div>
                    <div class="card-description">Διαχειριστείτε τις διπλωματικές εργασίες που επιβλέπετε και παρακολουθήστε την πρόοδο των φοιτητών σας.</div>
                </div>

                <div class="dashboard-card" onclick="showSection('newThesis')">
                    <div class="card-title">Δημιουργία Διπλωματικής</div>
                    <div class="card-description">Δημιουργήστε νέα θέματα διπλωματικών εργασιών και καθορίστε τις προδιαγραφές τους.</div>
                </div>

                <div class="dashboard-card" onclick="showSection('committees')">
                    <div class="card-title">Εξεταστικές Επιτροπές</div>
                    <div class="card-description">Δείτε τις προσκλήσεις για συμμετοχή σε εξεταστικές επιτροπές και διαχειριστείτε τις απαντήσεις σας.</div>
                </div>

                <div class="dashboard-card" onclick="showSection('grading')">
                    <div class="card-title">Αξιολόγηση & Βαθμολογία</div>
                    <div class="card-description">Αξιολογήστε και βαθμολογήστε τις διπλωματικές εργασίες που χρήζουν εξέτασης.</div>
                </div>

                <div class="dashboard-card" onclick="showSection('statistics')">
                    <div class="card-title">Στατιστικά</div>
                    <div class="card-description">Δείτε γραφήματα και στατιστικά για τις διπλωματικές που επιβλέπετε.</div>
                </div>

                <div class="dashboard-card" onclick="showSection('assignThesis')">
                    <div class="card-title">Ανάθεση Θέματος</div>
                    <div class="card-description">Αναθέστε διαθέσιμα θέματα σε φοιτητές που έχουν επικοινωνήσει μαζί σας.</div>
                </div>
            </div>

        </div>

        <!-- My Theses Section -->
        <div id="myTheses" class="section-view" style="display:none;">
            <div class="section-header">
                <h2>Οι Διπλωματικές μου</h2>
                <button onclick="showSection('dashboard')" class="back-btn">Επιστροφή</button>
            </div>
            <div class="filters">
                <select id="statusFilter" onchange="filterTheses()">
                    <option value="">Όλες οι καταστάσεις</option>
                    <option value="Ypo Anathesi">Υπό Ανάθεση</option>
                    <option value="Energi">Ενεργή</option>
                    <option value="Ypo Eksetasi">Υπό Εξέταση</option>
                    <option value="Peratomeni">Περατωμένη</option>
                    <option value="Akyromeni">Ακυρωμένη</option>
                </select>
                <select id="roleFilter" onchange="filterTheses()">
                    <option value="">Όλοι οι ρόλοι</option>
                    <option value="supervisor">Επιβλέπων</option>
                    <option value="examiner">Μέλος Τριμελούς</option>
                </select>
                <button onclick="exportTheses('csv')" class="export-btn">Εξαγωγή CSV</button>
                <button onclick="exportTheses('json')" class="export-btn">Εξαγωγή JSON</button>
            </div>
            <div id="thesesList" class="theses-list">
                <div class="loading">Φόρτωση διπλωματικών...</div>
            </div>
        </div>

        <!-- New Thesis Section -->
        <div id="newThesis" class="section-view" style="display:none;">
            <div class="section-header">
                <h2>Δημιουργία Νέας Διπλωματικής</h2>
                <button onclick="showSection('dashboard')" class="back-btn">Επιστροφή</button>
            </div>
            <div class="form-container">
                <form id="newThesisForm">
                    <div class="form-group">
                        <label for="thesisTitle">Τίτλος Διπλωματικής:</label>
                        <input type="text" id="thesisTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="thesisDescription">Περιγραφή (Σύνοψη):</label>
                        <textarea id="thesisDescription" name="description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="thesisPdf">Αρχείο PDF με αναλυτική παρουσίαση:</label>
                        <input type="file" id="thesisPdf" name="pdf_file" accept=".pdf">
                    </div>
                    <button type="submit" class="submit-btn">Δημιουργία Θέματος</button>
                </form>
            </div>
        </div>

        <!-- Committees Section -->
        <div id="committees" class="section-view" style="display:none;">
            <div class="section-header">
                <h2>Προσκλήσεις Εξεταστικών Επιτροπών</h2>
                <button onclick="showSection('dashboard')" class="back-btn">Επιστροφή</button>
            </div>
            <div id="invitationsList" class="invitations-list">
                <div class="loading">Φόρτωση προσκλήσεων...</div>
            </div>
        </div>

        <!-- Grading Section -->
        <div id="grading" class="section-view" style="display:none;">
            <div class="section-header">
                <h2>Αξιολόγηση & Βαθμολογία</h2>
                <button onclick="showSection('dashboard')" class="back-btn">Επιστροφή</button>
            </div>
            <div id="gradingList" class="grading-list">
                <div class="loading">Φόρτωση διπλωματικών για αξιολόγηση...</div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div id="statistics" class="section-view" style="display:none;">
            <div class="section-header">
                <h2>Στατιστικά</h2>
                <button onclick="showSection('dashboard')" class="back-btn">Επιστροφή</button>
            </div>
            <div class="statistics-container">
                <div class="chart-container">
                    <canvas id="completionTimeChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="gradeDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Assign Thesis Section -->
        <div id="assignThesis" class="section-view" style="display:none;">
            <div class="section-header">
                <h2>Ανάθεση Θέματος σε Φοιτητή</h2>
                <button onclick="showSection('dashboard')" class="back-btn">Επιστροφή</button>
            </div>
            <div class="form-container">
                <div class="form-group">
                    <label for="availableTheses">Επιλέξτε Διαθέσιμο Θέμα:</label>
                    <select id="availableTheses">
                        <option value="">Φόρτωση θεμάτων...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="studentSearch">Αναζήτηση Φοιτητή (ΑΜ ή Ονοματεπώνυμο):</label>
                    <input type="text" id="studentSearch" placeholder="Εισάγετε ΑΜ ή ονοματεπώνυμο">
                    <div id="studentResults" class="search-results"></div>
                </div>
                <button id="assignBtn" onclick="assignThesis()" class="submit-btn" disabled>Ανάθεση Θέματος</button>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="thesisModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Λεπτομέρειες Διπλωματικής</h3>
                <span class="close-btn" onclick="closeModal('thesisModal')">&times;</span>
            </div>
            <div id="modalBody" class="modal-body">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <div id="gradingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Βαθμολόγηση Διπλωματικής</h3>
                <span class="close-btn" onclick="closeModal('gradingModal')">&times;</span>
            </div>
            <div id="gradingModalBody" class="modal-body">
                <!-- Grading form will be loaded here -->
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ΤΜΗΥΠ - Σύστημα Διπλωματικών Εργασιών</p>
    </div>

    <script src="professor_dashboard.js"></script>
</body>
</html>