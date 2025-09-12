<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    header("Location: login.html");
    exit;
}

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
    <title>Professor Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            min-height: 100vh;
        }
        
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo h1 {
            color: #2d3436;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: #e17055;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #d63031;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .welcome h2 {
            color: #2d3436;
            margin-bottom: 10px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .card h3 {
            color: #2d3436;
            margin-bottom: 10px;
        }
        
        .card p {
            color: #636e72;
            line-height: 1.5;
        }
        
        .section {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section.active {
            display: block;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        
        .back-btn {
            background: #636e72;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .back-btn:hover {
            background: #2d3436;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .export-btn {
            background: #00b894;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .item-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .item-title {
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 10px;
        }
        
        .item-meta {
            font-size: 14px;
            color: #636e72;
            margin-bottom: 15px;
        }
        
        .item-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
        }
        
        .btn-primary { background: #0984e3; color: white; }
        .btn-success { background: #00b894; color: white; }
        .btn-warning { background: #fdcb6e; color: #2d3436; }
        .btn-danger { background: #e17055; color: white; }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        
        .submit-btn {
            background: #0984e3;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .submit-btn:disabled {
            background: #b2bec3;
            cursor: not-allowed;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            font-size: 24px;
            cursor: pointer;
            color: #636e72;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #636e72;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #636e72;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .search-results {
            position: relative;
        }
        
        .student-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
        }
        
        .student-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .student-item:hover {
            background: #f8f9fa;
        }
        
        .grading-form {
            display: grid;
            gap: 20px;
        }
        
        .criteria-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .grade-inputs {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
        }
        
        .grade-inputs input {
            width: 80px;
            text-align: center;
        }
        
        .final-grade {
            background: #0984e3;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
            font-size: 18px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .items-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            <h1>Σύστημα Διπλωματικών - Καθηγητής</h1>
        </div>
        <div class="user-info">
            <span>Καθηγητής: <?php echo htmlspecialchars($professor_name); ?></span>
            <a href="login.html" class="logout-btn">Αποσύνδεση</a>
        </div>
    </div>

    <div class="container">
        <!-- Dashboard -->
        <div id="dashboard">
            <div class="welcome">
                <h2>Καλώς ήρθατε στο σύστημα διπλωματικών εργασιών</h2>
                <p>Διαχειριστείτε τις διπλωματικές σας εργασίες</p>
            </div>

            <div class="dashboard-cards">
                <div class="card" onclick="showSection('myTheses')">
                    <h3>Οι Διπλωματικές μου</h3>
                    <p>Δείτε και διαχειριστείτε τις διπλωματικές εργασίες που επιβλέπετε</p>
                </div>

                <div class="card" onclick="showSection('newThesis')">
                    <h3>Νέα Διπλωματική</h3>
                    <p>Δημιουργήστε νέα θέματα διπλωματικών εργασιών</p>
                </div>

                <div class="card" onclick="showSection('committees')">
                    <h3>Εξεταστικές Επιτροπές</h3>
                    <p>Προσκλήσεις για συμμετοχή σε εξεταστικές επιτροπές</p>
                </div>

                <div class="card" onclick="showSection('grading')">
                    <h3>Βαθμολόγηση</h3>
                    <p>Βαθμολογήστε διπλωματικές εργασίες</p>
                </div>

                <div class="card" onclick="showSection('assignThesis')">
                    <h3>Ανάθεση Θέματος</h3>
                    <p>Αναθέστε θέματα σε φοιτητές</p>
                </div>

                <div class="card" onclick="showSection('statistics')">
                    <h3>Στατιστικά</h3>
                    <p>Δείτε στατιστικά για τις διπλωματικές σας</p>
                </div>
            </div>
        </div>

        <!-- My Theses Section -->
        <div id="myTheses" class="section">
            <div class="section-header">
                <h2>Οι Διπλωματικές μου</h2>
                <button class="back-btn" onclick="showSection('dashboard')"><- Πίσω</button>
            </div>
            
            <div class="filters">
                <select id="statusFilter" onchange="filterTheses()">
                    <option value="">Όλες οι καταστάσεις</option>
                    <option value="Ypo Anathesi">Υπό Ανάθεση</option>
                    <option value="Energi">Ενεργή</option>
                    <option value="Ypo Eksetasi">Υπό Εξέταση</option>
                    <option value="Peratomeni">Περατωμένη</option>
                </select>
                <button class="export-btn" onclick="exportTheses('csv')">Εξαγωγή CSV</button>
            </div>
            
            <div id="thesesList" class="items-grid">
                <div class="loading">Φόρτωση...</div>
            </div>
        </div>

        <!-- New Thesis Section -->
        <div id="newThesis" class="section">
            <div class="section-header">
                <h2>Δημιουργία Νέας Διπλωματικής</h2>
                <button class="back-btn" onclick="showSection('dashboard')"><- Πίσω</button>
            </div>
            
            <form id="newThesisForm">
                <div class="form-group">
                    <label>Τίτλος:</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Περιγραφή:</label>
                    <textarea name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Αρχείο PDF:</label>
                    <input type="file" name="pdf_file" accept=".pdf">
                </div>
                <button type="submit" class="submit-btn">Δημιουργία</button>
            </form>
        </div>

        <!-- Committee Invitations -->
        <div id="committees" class="section">
            <div class="section-header">
                <h2>Προσκλήσεις Εξεταστικών Επιτροπών</h2>
                <button class="back-btn" onclick="showSection('dashboard')"><- Πίσω</button>
            </div>
            
            <div id="invitationsList" class="items-grid">
                <div class="loading">Φόρτωση...</div>
            </div>
        </div>

        <!-- Grading Section -->
        <div id="grading" class="section">
            <div class="section-header">
                <h2>Βαθμολόγηση</h2>
                <button class="back-btn" onclick="showSection('dashboard')"><- Πίσω</button>
            </div>
            
            <div id="gradingList" class="items-grid">
                <div class="loading">Φόρτωση...</div>
            </div>
        </div>

        <!-- Assign Thesis -->
        <div id="assignThesis" class="section">
            <div class="section-header">
                <h2>Ανάθεση Θέματος</h2>
                <button class="back-btn" onclick="showSection('dashboard')"><- Πίσω</button>
            </div>
            
            <div class="form-group">
                <label>Επιλέξτε Θέμα:</label>
                <select id="availableTheses">
                    <option value="">Φόρτωση...</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Αναζήτηση Φοιτητή:</label>
                <input type="text" id="studentSearch" placeholder="ΑΜ ή Όνομα">
                <div id="studentResults" class="search-results"></div>
            </div>
            
            <button id="assignBtn" class="submit-btn" onclick="assignThesis()" disabled>Ανάθεση</button>
        </div>

        <!-- Statistics -->
        <div id="statistics" class="section">
            <div class="section-header">
                <h2>Στατιστικά</h2>
                <button class="back-btn" onclick="showSection('dashboard')"><- Πίσω</button>
            </div>
            
            <div id="statsContainer">
                <div class="loading">Φόρτωση στατιστικών...</div>
            </div>
        </div>
    </div>

    <!-- Modal for Details -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Λεπτομέρειες</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>

    <!-- Modal for Grading -->
    <div id="gradingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Βαθμολόγηση</h3>
                <span class="close" onclick="closeGradingModal()">&times;</span>
            </div>
            <div id="gradingModalBody"></div>
        </div>
    </div>

    <script>
        let currentData = [];
        let selectedStudentId = null;

        // Show section function
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            
            document.getElementById('dashboard').style.display = 'none';
            
            if (sectionId === 'dashboard') {
                document.getElementById('dashboard').style.display = 'block';
            } else {
                document.getElementById(sectionId).classList.add('active');
                loadSectionData(sectionId);
            }
        }

        // Load data for each section
        function loadSectionData(section) {
            switch(section) {
                case 'myTheses':
                    loadMyTheses();
                    break;
                case 'committees':
                    loadCommitteeInvitations();
                    break;
                case 'grading':
                    loadGradingList();
                    break;
                case 'assignThesis':
                    loadAvailableTheses();
                    setupStudentSearch();
                    break;
                case 'statistics':
                    loadStatistics();
                    break;
            }
        }

        // Load my theses
        async function loadMyTheses() {
            try {
                const response = await fetch('professor/professor_theses.php?action=get_my_theses');
                const data = await response.json();
                
                if (data.success) {
                    currentData = data.theses;
                    displayTheses(data.theses);
                } else {
                    document.getElementById('thesesList').innerHTML = '<div class="no-data">Σφάλμα φόρτωσης</div>';
                }
            } catch (error) {
                document.getElementById('thesesList').innerHTML = '<div class="no-data">Σφάλμα σύνδεσης</div>';
            }
        }

        // Display theses
        function displayTheses(theses) {
            const container = document.getElementById('thesesList');
            
            if (theses.length === 0) {
                container.innerHTML = '<div class="no-data">Δεν βρέθηκαν διπλωματικές</div>';
                return;
            }
            
            let html = '';
            theses.forEach(thesis => {
                const statusText = getStatusText(thesis.status);
                const roleText = thesis.role === 'supervisor' ? 'Επιβλέπων' : 'Εξεταστής';
                
                html += `
                    <div class="item-card">
                        <div class="item-title">${thesis.title}</div>
                        <div class="item-meta">
                            <div>Φοιτητής: ${thesis.student_name || 'Μη ανατεθειμένη'}</div>
                            <div>Ρόλος: ${roleText}</div>
                            <div>Κατάσταση: ${statusText}</div>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-primary" onclick="showThesisDetails(${thesis.id})">Προβολή</button>
                            ${getActionButtons(thesis)}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Get status text
        function getStatusText(status) {
            const statusMap = {
                'Ypo Anathesi': 'Υπό Ανάθεση',
                'Energi': 'Ενεργή',
                'Ypo Eksetasi': 'Υπό Εξέταση',
                'Peratomeni': 'Περατωμένη',
                'Akyromeni': 'Ακυρωμένη'
            };
            return statusMap[status] || status;
        }

        // Get action buttons
        function getActionButtons(thesis) {
    if (thesis.role !== 'supervisor') return '';
    
    switch (thesis.status) {
        case 'Ypo Anathesi':
            return '<button class="btn btn-danger" onclick="cancelThesis(' + thesis.id + ')">Ακύρωση</button>';
        case 'Energi':
            return '<button class="btn btn-success" onclick="moveToExamination(' + thesis.id + ')">Υπό Εξέταση</button>';
        case 'Ypo Eksetasi':
            return '<button class="btn btn-info" onclick="showThesisDetails(' + thesis.id + ')">Λεπτομέρειες</button>';
        default:
            return '';
    }
}

        // Filter theses
        function filterTheses() {
            const statusFilter = document.getElementById('statusFilter').value;
            let filtered = currentData;
            
            if (statusFilter) {
                filtered = currentData.filter(thesis => thesis.status === statusFilter);
            }
            
            displayTheses(filtered);
        }

        // Export theses
        function exportTheses(format) {
            window.open('professor/professor_theses.php?action=export_theses&format=' + format, '_blank');
        }

        // Show thesis details
        async function showThesisDetails(thesisId) {
            try {
                const response = await fetch('professor/professor_theses.php?action=get_thesis_details&id=' + thesisId);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('modalTitle').textContent = data.thesis.title;
                    document.getElementById('modalBody').innerHTML = `
                        <p><strong>Περιγραφή:</strong> ${data.thesis.description}</p>
                        <p><strong>Φοιτητής:</strong> ${data.thesis.student_name || 'Δεν έχει ανατεθεί'}</p>
                        <p><strong>Κατάσταση:</strong> ${getStatusText(data.thesis.status)}</p>
                        <p><strong>Ημερομηνία Έναρξης:</strong> ${data.thesis.starting_date || 'Δεν έχει οριστεί'}</p>
                    `;
                    document.getElementById('detailsModal').style.display = 'block';
                }
            } catch (error) {
                alert('Σφάλμα κατά τη φόρτωση');
            }
        }

        // Load committee invitations
        async function loadCommitteeInvitations() {
            try {
                const response = await fetch('professor/professor_committees.php?action=get_invitations');
                const data = await response.json();
                
                const container = document.getElementById('invitationsList');
                
                if (data.success && data.invitations.length > 0) {
                    let html = '';
                    data.invitations.forEach(inv => {
                        html += `
                            <div class="item-card">
                                <div class="item-title">${inv.thesis_title}</div>
                                <div class="item-meta">
                                    <div>Επιβλέπων: ${inv.supervisor_name}</div>
                                    <div>Φοιτητής: ${inv.student_name}</div>
                                </div>
                                <div class="item-actions">
                                    <button class="btn btn-success" onclick="respondInvitation(${inv.diplwm_id}, 'accept')">Αποδοχή</button>
                                    <button class="btn btn-danger" onclick="respondInvitation(${inv.diplwm_id}, 'reject')">Απόρριψη</button>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="no-data">Δεν υπάρχουν προσκλήσεις</div>';
                }
            } catch (error) {
                document.getElementById('invitationsList').innerHTML = '<div class="no-data">Σφάλμα φόρτωσης</div>';
            }
        }

        // Respond to invitation
        async function respondInvitation(diplwmId, response) {
            try {
                const res = await fetch('professor/professor_committees.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'respond_invitation',
                        diplwm_id: diplwmId,
                        response: response
                    })
                });
                
                const data = await res.json();
                if (data.success) {
                    alert('Η απάντηση καταχωρήθηκε');
                    loadCommitteeInvitations();
                }
            } catch (error) {
                alert('Σφάλμα');
            }
        }

        // Load grading list
        async function loadGradingList() {
            try {
                const response = await fetch('professor/professor_grading.php?action=get_grading_list');
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response was:', responseText);
            throw new Error('Invalid JSON response');
        }
        
                
                const container = document.getElementById('gradingList');
                
                if (data.success && data.theses.length > 0) {
                    let html = '';
                    data.theses.forEach(thesis => {
                        let statusText = 'Δεν έχει ενεργοποιηθεί';
                        let buttonHtml = '';
                        
                        if (thesis.examiner_role === 'supervisor' && !thesis.grading_enabled) {
                            buttonHtml = `<button class="btn btn-success" onclick="enableGrading(${thesis.id})">Ενεργοποίηση Βαθμολόγησης</button>`;
                        } else if (thesis.grading_enabled && !thesis.has_graded) {
                            statusText = 'Διαθέσιμο για βαθμολόγηση';
                            buttonHtml = `<button class="btn btn-warning" onclick="openGradingModal(${thesis.id})">Βαθμολόγηση</button>`;
                        } else if (thesis.has_graded) {
                            statusText = 'Έχετε βαθμολογήσει';
                            buttonHtml = `<button class="btn btn-primary" onclick="openGradingModal(${thesis.id})">Επεξεργασία Βαθμών</button>`;
                        }
                        
                        if (thesis.has_thesis_file) {
                            buttonHtml += ` <button class="btn btn-info" onclick="downloadThesisFile(${thesis.id})" style="margin-left: 5px;">Κατέβασμα Αρχείου</button>`;
                        }
                        
                        html += `
                            <div class="item-card">
                                <div class="item-title">${thesis.title}</div>
                                <div class="item-meta">
                                    <div>Φοιτητής: ${thesis.student_name} (${thesis.student_am})</div>
                                    <div>Ρόλος: ${getRoleText(thesis.examiner_role)}</div>
                                    <div>Κατάσταση: ${statusText}</div>
                                    ${thesis.present_date ? `<div>Ημερομηνία: ${thesis.present_date}</div>` : ''}
                                    ${thesis.final_grade ? `<div>Τελικός Βαθμός: ${thesis.final_grade}/10</div>` : ''}
                                </div>
                                <div class="item-actions">
                                    ${buttonHtml}
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else if (data.success) {
                    container.innerHTML = '<div class="no-data">Δεν υπάρχουν διπλωματικές για βαθμολόγηση</div>';
                } else {
                    container.innerHTML = `<div class="no-data">Σφάλμα: ${data.message}</div>`;
                }
            } 
            catch (error) {
        console.error('Grading list error:', error);
        console.error('Error details:', error.message);
        document.getElementById('gradingList').innerHTML = '<div class="no-data">Σφάλμα σύνδεσης</div>';
    }
        }
        // Enable grading
        async function enableGrading(thesisId) {
            if (!confirm('Ενεργοποίηση βαθμολόγησης; Μετά από αυτό όλα τα μέλη της τριμελούς θα μπορούν να βαθμολογήσουν.')) {
                return;
            }
            
            try {
                const response = await fetch(`professor/professor_grading.php?action=enable_grading&thesis_id=${thesisId}`);
                const data = await response.json();
                
                if (data.success) {
                    alert('Η βαθμολόγηση ενεργοποιήθηκε επιτυχώς');
                    loadGradingList();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            } catch (error) {
                console.error('Enable grading error:', error);
                alert('Σφάλμα ενεργοποίησης');
            }
        }
        // Download thesis file
        function downloadThesisFile(thesisId) {
            window.open(`professor/professor_grading.php?action=download_thesis_file&thesis_id=${thesisId}`, '_blank');
        }

        // Get role text 
        function getRoleText(role) {
            switch (role) {
                case 'supervisor': return 'Επιβλέπων';
                case 'examiner1': return 'Μέλος Τριμελούς 1';
                case 'examiner2': return 'Μέλος Τριμελούς 2';
                default: return 'Άγνωστος ρόλος';
            }
        }

        // Open grading modal
        async function openGradingModal(thesisId) {
            try {
                const response = await fetch('professor/professor_grading.php?action=get_grading_form&thesis_id=' + thesisId);
                const data = await response.json();
                
                if (data.success) {
                    const thesis = data.thesis;
                    const grades = data.grades;
                    const examinerRole = data.examiner_role;
                    
                    // Προσδιορισμός των βαθμών που αντιστοιχούν στον τρέχοντα εξεταστή
                    let currentGrades = { grade1: '', grade2: '', grade3: '', grade4: '' };
                    if (grades) {
                        switch (examinerRole) {
                            case 'supervisor':
                                currentGrades = {
                                    grade1: grades.grade1_1 || '',
                                    grade2: grades.grade1_2 || '',
                                    grade3: grades.grade1_3 || '',
                                    grade4: grades.grade1_4 || ''
                                };
                                break;
                            case 'examiner1':
                                currentGrades = {
                                    grade1: grades.grade2_1 || '',
                                    grade2: grades.grade2_2 || '',
                                    grade3: grades.grade2_3 || '',
                                    grade4: grades.grade2_4 || ''
                                };
                                break;
                            case 'examiner2':
                                currentGrades = {
                                    grade1: grades.grade3_1 || '',
                                    grade2: grades.grade3_2 || '',
                                    grade3: grades.grade3_3 || '',
                                    grade4: grades.grade3_4 || ''
                                };
                                break;
                        }
                    }
                    
                    document.getElementById('gradingModalBody').innerHTML = `
                        <form id="gradingForm" class="grading-form">
                            <h4>Βαθμολόγηση: ${thesis.title}</h4>
                            <p><strong>Φοιτητής:</strong> ${thesis.student_name} (${thesis.student_am})</p>
                            <p><strong>Ρόλος σας:</strong> ${getRoleText(examinerRole)}</p>
                            
                            <div class="committee-info" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                <h5>Τριμελής Επιτροπή:</h5>
                                <p><strong>Επιβλέπων:</strong> ${thesis.supervisor_name || 'Δεν έχει οριστεί'}</p>
                                <p><strong>Μέλος 1:</strong> ${thesis.examiner1_name || 'Δεν έχει οριστεί'}</p>
                                <p><strong>Μέλος 2:</strong> ${thesis.examiner2_name || 'Δεν έχει οριστεί'}</p>
                            </div>
                            
                            <div class="grading-criteria">
                                <h5>Κριτήρια Αξιολόγησης</h5>
                                <p style="margin-bottom: 20px; font-size: 14px; color: #666;">
                                    Δώστε βαθμούς από 0 έως 10 για κάθε κριτήριο
                                </p>
                                
                                <div class="form-group">
                                    <label>Ποιότητα της Δ.Ε. (60%):</label>
                                    <input type="number" name="grade1" min="0" max="10" step="0.1" 
                                           value="${currentGrades.grade1}" required 
                                           placeholder="Προσθέστε τον βαθμό σας">
                                </div>
                                
                                <div class="form-group">
                                    <label>Χρονικό Διάστημα (15%):</label>
                                    <input type="number" name="grade2" min="0" max="10" step="0.1" 
                                           value="${currentGrades.grade2}" required
                                           placeholder="Προσθέστε τον βαθμό σας">
                                </div>
                                
                                <div class="form-group">
                                    <label>Ποιότητα κια Πληρότητα (15%):</label>
                                    <input type="number" name="grade3" min="0" max="10" step="0.1" 
                                           value="${currentGrades.grade3}" required
                                           placeholder="Προσθέστε τον βαθμό σας">
                                </div>
                                
                                <div class="form-group">
                                    <label>Συνολική Εικόνα (10%):</label>
                                    <input type="number" name="grade4" min="0" max="10" step="0.1" 
                                           value="${currentGrades.grade4}" required
                                           placeholder="Προσθέστε τον βαθμό σας">
                                </div>
                            </div>
                            
                            <div class="weighted-average" style="margin: 20px 0; padding: 15px; background: #e8f4f8; border-radius: 8px;">
                                <h6>Σταθμισμένος Μέσος Όρος των Βαθμών σας:</h6>
                                <div id="weightedAverage" style="font-size: 18px; font-weight: bold; color: #2c3e50;">
                                    -
                                </div>
                                <small style="color: #666;">
                                    Τύπος: (Περιεχόμενο × 0.6) + (Παρουσίαση × 0.15) + (Συγγραφή × 0.15) + (Δυσκολία × 0.1)
                                </small>
                            </div>
                            
                            ${grades && grades.final_grade ? `
                                <div style="margin: 20px 0; padding: 15px; background: #d4edda; border-radius: 8px; text-align: center;">
                                    <h6>Τελικός Βαθμός Διπλωματικής</h6>
                                    <div style="font-size: 24px; font-weight: bold; color: #155724;">
                                        ${grades.final_grade}/10
                                    </div>
                                    <small>Μέσος όρος των σταθμισμένων βαθμών όλων των εξεταστών</small>
                                </div>
                            ` : ''}
                            
                            <input type="hidden" name="examiner_role" value="${examinerRole}">
                            <button type="submit" class="submit-btn">Καταχώρηση Βαθμών</button>
                        </form>
                    `;
                    
                    document.getElementById('gradingModal').style.display = 'block';
                    
                    // Add event listeners for grade calculation
                    const inputs = document.querySelectorAll('#gradingForm input[type="number"]');
                    inputs.forEach(input => {
                        input.addEventListener('input', calculateWeightedAverage);
                    });
                    
                    // Setup form submission
                    document.getElementById('gradingForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        submitGrades(thesisId, examinerRole);
                    });
                    
                    calculateWeightedAverage(); // Initial calculation
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            } catch (error) {
                console.error('Grading modal error:', error);
                alert('Σφάλμα φόρτωσης φόρμας βαθμολόγησης');
            }
        }

        // Calculate weighted average
        function calculateWeightedAverage() {
            const form = document.getElementById('gradingForm');
            if (!form) return;
            
            const grade1 = parseFloat(form.grade1.value) || 0;
            const grade2 = parseFloat(form.grade2.value) || 0;
            const grade3 = parseFloat(form.grade3.value) || 0;
            const grade4 = parseFloat(form.grade4.value) || 0;
            
            // Σταθμισμένος μέσος όρος: 60% + 15% + 15% + 10%
            const weightedAvg = (grade1 * 0.6) + (grade2 * 0.15) + (grade3 * 0.15) + (grade4 * 0.1);
            
            const avgElement = document.getElementById('weightedAverage');
            if (avgElement) {
                if (grade1 || grade2 || grade3 || grade4) {
                    avgElement.textContent = weightedAvg.toFixed(2) + '/10';
                    avgElement.style.color = weightedAvg >= 5 ? '#27ae60' : '#e74c3c';
                } else {
                    avgElement.textContent = '-';
                    avgElement.style.color = '#2c3e50';
                }
            }
        }

        // Calculate final grade
        function calculateGrade() {
            const form = document.getElementById('gradingForm');
            if (!form) return;
            
            const inputs = form.querySelectorAll('input[type="number"]');
            let allFilled = true;
            let grades = {};
            
            inputs.forEach(input => {
                if (input.value === '') {
                    allFilled = false;
                } else {
                    grades[input.name] = parseFloat(input.value);
                }
            });
            
            if (!allFilled) {
                document.getElementById('finalGrade').textContent = '-';
                return;
            }
            
            // Calculate according to the formula
            const c1 = grades.grade1_1 * 0.6 + grades.grade1_2 * 0.15 + grades.grade1_3 * 0.15 + grades.grade1_4 * 0.1;
            const c2 = grades.grade2_1 * 0.6 + grades.grade2_2 * 0.15 + grades.grade2_3 * 0.15 + grades.grade2_4 * 0.1;
            const c3 = grades.grade3_1 * 0.6 + grades.grade3_2 * 0.15 + grades.grade3_3 * 0.15 + grades.grade3_4 * 0.1;
            
            const finalGrade = (c1 + c2 + c3) / 3;
            document.getElementById('finalGrade').textContent = finalGrade.toFixed(2);
        }

        // Submit grades
        async function submitGrades(thesisId, examinerRole) {
            const form = document.getElementById('gradingForm');
            const formData = new FormData(form);
            
            const grades = {
                grade1: parseFloat(formData.get('grade1')),
                grade2: parseFloat(formData.get('grade2')),
                grade3: parseFloat(formData.get('grade3')),
                grade4: parseFloat(formData.get('grade4'))
            };
            
            console.log('Sending grades:', {
            action: 'submit_grades',
            thesis_id: thesisId,
            grades: grades,
            examiner_role: examinerRole
            });

            
            // Validation
            for (let key in grades) {
                if (isNaN(grades[key]) || grades[key] < 0 || grades[key] > 10) {
                    alert(`Παρακαλώ εισάγετε έγκυρο βαθμό (0-10) για όλα τα κριτήρια`);
                    return;
                }
            }
            
            try {
                const response = await fetch('professor/professor_grading.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'submit_grades',
                        thesis_id: thesisId,
                        grades: grades,
                        examiner_role: examinerRole
                    })
                });
                
                const data = await response.json();
                const responseText = await response.text();
                console.log('Submit grades response:', responseText);
                
                if (data.success) {
                    alert('Οι βαθμοί καταχωρήθηκαν επιτυχώς');
                    closeGradingModal();
                    loadGradingList();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            } catch (error) {
                console.error('Submit grades error:', error);
                alert('Σφάλμα κατά την καταχώρηση');
            }
        }

        // Load available theses for assignment
        async function loadAvailableTheses() {
            try {
                const response = await fetch('professor/professor_students.php?action=get_available_theses');
                const data = await response.json();
                
                const select = document.getElementById('availableTheses');
                
                if (data.success) {
                    let html = '<option value="">Επιλέξτε θέμα...</option>';
                    data.theses.forEach(thesis => {
                        html += `<option value="${thesis.id}">${thesis.title}</option>`;
                    });
                    select.innerHTML = html;
                }
            } catch (error) {
                console.log('Σφάλμα φόρτωσης θεμάτων');
            }
        }

        // Setup student search
        function setupStudentSearch() {
            const searchInput = document.getElementById('studentSearch');
            const thesesSelect = document.getElementById('availableTheses');
            const assignBtn = document.getElementById('assignBtn');
            
            let searchTimer;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                const query = this.value;
                
                if (query.length < 2) {
                    document.getElementById('studentResults').innerHTML = '';
                    return;
                }
                
                searchTimer = setTimeout(() => {
                    searchStudents(query);
                }, 300);
            });
            
            thesesSelect.addEventListener('change', function() {
                updateAssignButton();
            });
        }

        // Search students
        async function searchStudents(query) {
            try {
                const response = await fetch('professor/professor_students.php?action=search_students&query=' + encodeURIComponent(query));
                const data = await response.json();
                
                const resultsDiv = document.getElementById('studentResults');
                
                if (data.success && data.students.length > 0) {
                    let html = '<div class="student-results">';
                    data.students.forEach(student => {
                        const disabled = student.has_active_thesis ? 'style="opacity:0.5"' : '';
                        html += `
                            <div class="student-item" ${disabled} onclick="selectStudent(${student.ID}, '${student.name} ${student.surname}', '${student.AM}', ${student.has_active_thesis})">
                                <div><strong>${student.name} ${student.surname}</strong></div>
                                <div>ΑΜ: ${student.AM}${student.has_active_thesis ? ' (Έχει ενεργή διπλωματική)' : ''}</div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = '<div class="student-results"><div class="student-item">Δεν βρέθηκαν φοιτητές</div></div>';
                }
            } catch (error) {
                console.log('Σφάλμα αναζήτησης');
            }
        }

        // Select student
        function selectStudent(studentId, studentName, studentAM, hasActive) {
            if (hasActive) {
                alert('Ο φοιτητής έχει ήδη ενεργή διπλωματική');
                return;
            }
            
            selectedStudentId = studentId;
            document.getElementById('studentSearch').value = studentName + ' (' + studentAM + ')';
            document.getElementById('studentResults').innerHTML = '';
            updateAssignButton();
        }

        // Update assign button state
        function updateAssignButton() {
            const thesisId = document.getElementById('availableTheses').value;
            const assignBtn = document.getElementById('assignBtn');
            
            if (thesisId && selectedStudentId) {
                assignBtn.disabled = false;
            } else {
                assignBtn.disabled = true;
            }
        }

        // Assign thesis
        async function assignThesis() {
            const thesisId = document.getElementById('availableTheses').value;
            
            if (!thesisId || !selectedStudentId) {
                alert('Παρακαλώ επιλέξτε θέμα και φοιτητή');
                return;
            }
            
            try {
                const response = await fetch('professor/professor_theses.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'assign_thesis',
                        thesis_id: thesisId,
                        student_id: selectedStudentId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Το θέμα ανατέθηκε επιτυχώς');
                    document.getElementById('availableTheses').value = '';
                    document.getElementById('studentSearch').value = '';
                    selectedStudentId = null;
                    document.getElementById('assignBtn').disabled = true;
                    loadAvailableTheses();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            } catch (error) {
                alert('Σφάλμα κατά την ανάθεση');
            }
        }

        // Load statistics
        async function loadStatistics() {
            try {
                const response = await fetch('professor/professor_statistics.php?action=get_dashboard_summary');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.summary;
                    document.getElementById('statsContainer').innerHTML = `
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                                <h4>Συνολικές Διπλωματικές</h4>
                                <div style="font-size: 24px; color: #0984e3;">${stats.total_supervised}</div>
                                <div>Ως Επιβλέπων</div>
                            </div>
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                                <h4>Ενεργές</h4>
                                <div style="font-size: 24px; color: #00b894;">${stats.active_theses}</div>
                                <div>Τρέχουσες</div>
                            </div>
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                                <h4>Υπό Εξέταση</h4>
                                <div style="font-size: 24px; color: #fdcb6e;">${stats.pending_grading}</div>
                                <div>Για Βαθμολόγηση</div>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('statsContainer').innerHTML = '<div class="no-data">Σφάλμα φόρτωσης στατιστικών</div>';
            }
        }

        // Thesis actions
        async function moveToExamination(thesisId) {
            if (!confirm('Μεταφορά στη φάση εξέτασης;')) return;
            
            try {
                const response = await fetch('professor/professor_theses.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'move_to_examination',
                        thesis_id: thesisId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Η διπλωματική μεταφέρθηκε στη φάση εξέτασης');
                    loadMyTheses();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            } catch (error) {
                alert('Σφάλμα');
            }
        }

        async function cancelThesis(thesisId) {
            const reason = prompt('Λόγος ακύρωσης:');
            if (!reason) return;
            
            if (!confirm('Ακύρωση διπλωματικής;')) return;
            
            try {
                const response = await fetch('professor/professor_theses.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'cancel_thesis',
                        thesis_id: thesisId,
                        cancel_reason: reason
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Η διπλωματική ακυρώθηκε');
                    loadMyTheses();
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            } catch (error) {
                alert('Σφάλμα');
            }
        }

        // Form submission for new thesis
        document.getElementById('newThesisForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_new_thesis');
            
            try {
                const response = await fetch('professor/professor_theses.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Το θέμα δημιουργήθηκε επιτυχώς');
                    this.reset();
                    showSection('dashboard');
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            } catch (error) {
                alert('Σφάλμα κατά τη δημιουργία');
            }
        });

        // Modal functions
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        function closeGradingModal() {
            document.getElementById('gradingModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const detailsModal = document.getElementById('detailsModal');
            const gradingModal = document.getElementById('gradingModal');
            
            if (e.target === detailsModal) {
                closeModal();
            }
            if (e.target === gradingModal) {
                closeGradingModal();
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Professor Dashboard loaded');
        });
    </script>
</body>
</html>
