<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
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

        .section {
            display: none;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .section.active {
            display: block;
        }

        .section-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .back-btn {
            background: #95a5a6;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .thesis-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .status-assigned { background: #fff3cd; color: #856404; }
        .status-active { background: #d4edda; color: #155724; }
        .status-review { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .committee-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .committee-member {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .file-upload-area {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            background: #f8f9ff;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
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
                <span class="user-name">Φοιτητής: <?php echo htmlspecialchars($student_name); ?></span>
                <a href="login.html" class="logout-btn">Αποσύνδεση</a>
            </div>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div id="dashboard" class="main-container">
        <div class="welcome-section">
            <h2>Καλώς ήρθες!</h2>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card" onclick="showSection('thesisSection')">
                <div class="card-title">Προβολή θέματος</div>
            </div>

            <div class="dashboard-card" onclick="showSection('profileSection')">
                <div class="card-title">Επεξεργασία Προφίλ</div>
            </div>

            <div class="dashboard-card" onclick="showSection('manageSection')">
                <div class="card-title">Διαχείριση διπλωματικής εργασίας</div>
            </div>
        </div>
    </div>

    <!-- Thesis Section -->
    <div id="thesisSection" class="section">
        <div class="section-content">
            <button class="back-btn" onclick="showDashboard()">← Πίσω</button>
            <h2>Προβολή θέματος</h2>
            <div id="thesisDisplay">
                <p>Φόρτωση στοιχείων διπλωματικής...</p>
            </div>
        </div>
    </div>

    <!-- Profile Section -->
    <div id="profileSection" class="section">
        <div class="section-content">
            <button class="back-btn" onclick="showDashboard()">← Πίσω</button>
            <h2>Επεξεργασία Προφίλ</h2>
            <div id="profileDisplay">
                <p>Φόρτωση προφίλ...</p>
            </div>
        </div>
    </div>

    <!-- Manage Section -->
    <div id="manageSection" class="section">
        <div class="section-content">
            <button class="back-btn" onclick="showDashboard()">← Πίσω</button>
            <h2>Διαχείριση διπλωματικής εργασίας</h2>
            <div id="manageDisplay">
                <p>Φόρτωση επιλογών διαχείρισης...</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ΤΜΗΥΠ - Σύστημα Διαχείρισης Διπλωματικών Εργασιών</p>
    </div>

    <script>
        // Show/Hide sections
        function showSection(sectionId) {
            document.getElementById('dashboard').style.display = 'none';
            
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));
            
            document.getElementById(sectionId).classList.add('active');
            
            // Load data for each section
            if (sectionId === 'thesisSection') {
                loadThesisData();
            } else if (sectionId === 'profileSection') {
                loadProfileData();
            } else if (sectionId === 'manageSection') {
                loadManageData();
            }
        }

        function showDashboard() {
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));
            
            document.getElementById('dashboard').style.display = 'block';
        }

        // Load thesis data from database
        async function loadThesisData() {
            const display = document.getElementById('thesisDisplay');
            
            try {
                const response = await fetch('student_api.php?action=getThesis');
                const data = await response.json();
                
                if (data.success) {
                    if (data.thesis) {
                        renderThesis(data.thesis, data.time_passed);
                    } else {
                        display.innerHTML = `
                            <div class="thesis-info">
                                <h3>Δεν έχετε ανατεθεί διπλωματική εργασία</h3>
                                <p>Επικοινωνήστε με έναν καθηγητή για να συζητήσετε τις διαθέσιμες επιλογές.</p>
                            </div>
                        `;
                    }
                } else {
                    display.innerHTML = `<div class="error">Σφάλμα: ${data.message}</div>`;
                }
            } catch (error) {
                console.error('Error loading thesis:', error);
                display.innerHTML = `<div class="error">Σφάλμα φόρτωσης δεδομένων</div>`;
            }
        }

        // Render thesis information
        function renderThesis(thesis, timePassed) {
            const display = document.getElementById('thesisDisplay');
            
            // Status mapping
            const statusMap = {
                'Ypo Anathesi': { text: 'Υπό Ανάθεση', class: 'status-assigned' },
                'Energi': { text: 'Ενεργή', class: 'status-active' },
                'Ypo Eksetasi': { text: 'Υπό Εξέταση', class: 'status-review' },
                'Peratomeni': { text: 'Περατωμένη', class: 'status-completed' },
                'Akyromeni': { text: 'Ακυρωμένη', class: 'status-cancelled' }
            };
            
            const status = statusMap[thesis.status.trim()] || { text: thesis.status, class: 'status-assigned' };
            
            let timePassedText = 'Δεν έχει καθοριστεί';
            if (timePassed && thesis.starting_date) {
                if (timePassed.years > 0) {
                    timePassedText = `${timePassed.years} έτος/η και ${timePassed.months} μήνες`;
                } else if (timePassed.months > 0) {
                    timePassedText = `${timePassed.months} μήνες και ${timePassed.days} ημέρες`;
                } else {
                    timePassedText = `${timePassed.days} ημέρες`;
                }
            }

            let committeeHtml = '';
            if (thesis.supervisor_name) {
                committeeHtml = `
                    <div class="committee-list">
                        <div class="committee-member">
                            <strong>Επιβλέπων</strong><br>
                            ${thesis.supervisor_name}
                        </div>
                        ${thesis.examiner1_name ? `
                            <div class="committee-member">
                                <strong>Μέλος τριμελούς 1</strong><br>
                                ${thesis.examiner1_name}
                            </div>
                        ` : ''}
                        ${thesis.examiner2_name ? `
                            <div class="committee-member">
                                <strong>Μέλος τριμελούς 2</strong><br>
                                ${thesis.examiner2_name}
                            </div>
                        ` : ''}
                    </div>
                `;
            } else {
                committeeHtml = '<p>Δεν έχει οριστεί τριμελής επιτροπή</p>';
            }

            display.innerHTML = `
                <div class="thesis-info">
                    <h3>${thesis.title}
                        <span class="status-badge ${status.class}">${status.text}</span>
                    </h3>
                    <p><strong>Περιγραφή:</strong> ${thesis.description}</p>
                    ${thesis.starting_date ? `<p><strong>Ημερομηνία ανάθεσης:</strong> ${thesis.starting_date}</p>` : ''}
                    <p><strong>Χρόνος από ανάθεση:</strong> ${timePassedText}</p>
                    
                    <h4 style="margin-top: 20px;">Τριμελής Επιτροπή</h4>
                    ${committeeHtml}
                    
                    ${thesis.final_grade ? `
                        <div style="margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 8px;">
                            <h4>Βαθμολογία</h4>
                            <p style="font-size: 20px; font-weight: bold; color: #27ae60;">
                                Τελικός Βαθμός: ${thesis.final_grade}/10
                            </p>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        // Load profile data
        async function loadProfileData() {
            const display = document.getElementById('profileDisplay');
            
            try {
                const response = await fetch('student_api.php?action=getProfile');
                const data = await response.json();
                
                if (data.success) {
                    renderProfileForm(data.profile);
                } else {
                    display.innerHTML = `<div class="error">Σφάλμα: ${data.message}</div>`;
                }
            } catch (error) {
                console.error('Error loading profile:', error);
                display.innerHTML = `<div class="error">Σφάλμα φόρτωσης προφίλ</div>`;
            }
        }

        // Render profile form
        function renderProfileForm(profile) {
            const display = document.getElementById('profileDisplay');
            
            display.innerHTML = `
                <form id="profileForm" onsubmit="updateProfile(event)">
                    <div class="form-group">
                        <label>Όνομα</label>
                        <input type="text" value="${profile.name}" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Επώνυμο</label>
                        <input type="text" value="${profile.surname}" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>ΑΜ</label>
                        <input type="text" value="${profile.AM}" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="street">Οδός *</label>
                        <input type="text" id="street" name="street" value="${profile.street || ''}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="street_num">Αριθμός *</label>
                        <input type="number" id="street_num" name="street_num" value="${profile.street_num || ''}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="city">Πόλη *</label>
                        <input type="text" id="city" name="city" value="${profile.city || ''}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="postcode">Τ.Κ. *</label>
                        <input type="text" id="postcode" name="postcode" value="${profile.postcode || ''}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email επικοινωνίας *</label>
                        <input type="email" id="email" name="email" value="${profile.email || ''}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mobile_phone">Κινητό τηλέφωνο *</label>
                        <input type="tel" id="mobile_phone" name="mobile_phone" value="${profile.mobile_phone || ''}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="landline_phone">Σταθερό τηλέφωνο</label>
                        <input type="tel" id="landline_phone" name="landline_phone" value="${profile.landline_phone || ''}">
                    </div>
                    
                    <button type="submit" class="btn">Ενημέρωση Στοιχείων</button>
                </form>
            `;
        }

        // Update profile
        async function updateProfile(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('student_api.php?action=updateProfile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('success', 'Τα στοιχεία ενημερώθηκαν επιτυχώς');
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                showMessage('error', 'Σφάλμα ενημέρωσης στοιχείων');
            }
        }

        // Load management options based on thesis status
        async function loadManageData() {
            const display = document.getElementById('manageDisplay');
            
            try {
                const response = await fetch('student_api.php?action=getManageOptions');
                const data = await response.json();
                
                if (data.success) {
                    renderManageOptions(data);
                } else {
                    display.innerHTML = `<div class="error">Σφάλμα: ${data.message}</div>`;
                }
            } catch (error) {
                console.error('Error loading manage options:', error);
                display.innerHTML = `<div class="error">Σφάλμα φόρτωσης επιλογών</div>`;
            }
        }

        // Render management options based on thesis status
        function renderManageOptions(data) {
            const display = document.getElementById('manageDisplay');
            
            if (!data.thesis) {
                display.innerHTML = `
                    <div class="thesis-info">
                        <h3>Δεν έχετε ανατεθεί διπλωματική εργασία</h3>
                        <p>Δεν υπάρχουν διαθέσιμες ενέργειες διαχείρισης.</p>
                    </div>
                `;
                return;
            }

            const thesis = data.thesis;
            const status = thesis.status.trim();

            let content = `<h3>Διαχείριση: ${thesis.title}</h3>`;

            switch (status) {
                case 'Ypo Anathesi':
                    content += `
                        <div class="thesis-info">
                            <p><strong>Κατάσταση:</strong> Υπό Ανάθεση</p>
                            <p>Επιλέξτε τους Διδάσκοντες που επιθυμείτε να είναι μέλη της τριμελούς επιτροπής.</p>
                            <div id="inviteProfessors">
                                <div class="form-group">
                                    <label for="professor1">Επιλογή 1ου μέλους τριμελούς:</label>
                                    <select id="professor1" name="professor1">
                                        <option value="">-- Επιλέξτε καθηγητή --</option>
                                        ${data.available_professors.map(prof => 
                                            `<option value="${prof.ID}">${prof.name} ${prof.surname}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="professor2">Επιλογή 2ου μέλους τριμελούς:</label>
                                    <select id="professor2" name="professor2">
                                        <option value="">-- Επιλέξτε καθηγητή --</option>
                                        ${data.available_professors.map(prof => 
                                            `<option value="${prof.ID}">${prof.name} ${prof.surname}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <button class="btn" onclick="inviteProfessors()">Αποστολή Προσκλήσεων</button>
                            </div>
                        </div>
                    `;
                    break;

                case 'Ypo Eksetasi':
                    content += `
                        <div class="thesis-info">
                            <p><strong>Κατάσταση:</strong> Υπό Εξέταση</p>
                            <p>Αναρτήστε το πρόχειρο κείμενο της διπλωματικής και καταχωρήστε τα στοιχεία παρουσίασης.</p>
                            
                            <div class="form-group">
                                <label>Ανέβασμα κειμένου διπλωματικής:</label>
                                <div class="file-upload-area" onclick="document.getElementById('thesisFile').click()">
                                    <input type="file" id="thesisFile" style="display: none;" accept=".pdf,.doc,.docx" onchange="uploadThesisFile()">
                                    <p>Κλικ για ανέβασμα αρχείου (PDF, DOC, DOCX)</p>
                                    <small>Μέγιστο μέγεθος: 10MB</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="presentDate">Ημερομηνία παρουσίασης:</label>
                                <input type="date" id="presentDate" value="${thesis.present_date || ''}">
                            </div>

                            <div class="form-group">
                                <label for="presentTime">Ώρα παρουσίασης:</label>
                                <input type="time" id="presentTime" value="${thesis.present_time || ''}">
                            </div>

                            <div class="form-group">
                                <label for="presentVenue">Τόπος παρουσίασης:</label>
                                <input type="text" id="presentVenue" placeholder="π.χ. Αίθουσα Α1 ή διαδικτυακά" value="${thesis.present_venue || ''}">
                            </div>

                            <div class="form-group">
                                <label for="libLink">Σύνδεσμος αποθετηρίου βιβλιοθήκης (Νημερτής):</label>
                                <input type="url" id="libLink" placeholder="https://..." value="${thesis.lib_link || ''}">
                            </div>

                            <button class="btn" onclick="updateThesisDetails()">Ενημέρωση Στοιχείων</button>
                        </div>
                    `;
                    break;

                case 'Peratomeni':
                    content += `
                        <div class="thesis-info">
                            <p><strong>Κατάσταση:</strong> Περατωμένη</p>
                            <p>Διατηρείται μόνο η δυνατότητα προβολής των πληροφοριών της ΔΕ, των αλλαγών κατάστασης, και του πρακτικού εξέτασης.</p>
                            
                            ${thesis.final_grade ? `
                                <div style="margin-top: 20px; padding: 20px; background: #e8f5e8; border-radius: 8px;">
                                    <h4>Πρακτικό Εξέτασης</h4>
                                    <p><strong>Τελικός Βαθμός:</strong> ${thesis.final_grade}/10</p>
                                    <button class="btn" onclick="viewExamRecord()">Προβολή Πρακτικού</button>
                                </div>
                            ` : '<p>Αναμονή καταχώρησης βαθμολογίας.</p>'}
                        </div>
                    `;
                    break;

                default:
                    content += `
                        <div class="thesis-info">
                            <p><strong>Κατάσταση:</strong> ${status}</p>
                            <p>Δεν υπάρχουν διαθέσιμες ενέργειες για αυτή την κατάσταση.</p>
                        </div>
                    `;
            }

            display.innerHTML = content;
        }

        // Invite professors to committee
        async function inviteProfessors() {
            const prof1 = document.getElementById('professor1').value;
            const prof2 = document.getElementById('professor2').value;

            if (!prof1 || !prof2) {
                showMessage('error', 'Παρακαλώ επιλέξτε και τους δύο καθηγητές');
                return;
            }

            if (prof1 === prof2) {
                showMessage('error', 'Παρακαλώ επιλέξτε διαφορετικούς καθηγητές');
                return;
            }

            try {
                const response = await fetch('student_api.php?action=inviteProfessors', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ professor1: prof1, professor2: prof2 })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('success', 'Οι προσκλήσεις στάλθηκαν επιτυχώς');
                    loadManageData(); // Refresh the section
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                console.error('Error inviting professors:', error);
                showMessage('error', 'Σφάλμα αποστολής προσκλήσεων');
            }
        }

        // Upload thesis file
        async function uploadThesisFile() {
            const fileInput = document.getElementById('thesisFile');
            const file = fileInput.files[0];

            if (!file) return;

            if (file.size > 10 * 1024 * 1024) { // 10MB
                showMessage('error', 'Το αρχείο δεν μπορεί να είναι μεγαλύτερο από 10MB');
                return;
            }

            const formData = new FormData();
            formData.append('thesis_file', file);

            try {
                showMessage('success', 'Ανέβασμα αρχείου...');

                const response = await fetch('student_api.php?action=uploadFile', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('success', `Το αρχείο "${file.name}" ανέβηκε επιτυχώς`);
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                console.error('Error uploading file:', error);
                showMessage('error', 'Σφάλμα ανεβάσματος αρχείου');
            }
        }

        // Update thesis details
        async function updateThesisDetails() {
            const data = {
                present_date: document.getElementById('presentDate').value,
                present_time: document.getElementById('presentTime').value,
                present_venue: document.getElementById('presentVenue').value,
                lib_link: document.getElementById('libLink').value
            };

            try {
                const response = await fetch('student_api.php?action=updateThesisDetails', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('success', 'Τα στοιχεία ενημερώθηκαν επιτυχώς');
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                console.error('Error updating thesis details:', error);
                showMessage('error', 'Σφάλμα ενημέρωσης στοιχείων');
            }
        }

        // View exam record (HTML format)
        async function viewExamRecord() {
            try {
                const response = await fetch('student/getExamRecord.php');
                const result = await response.json();

                if (result.success) {
                    // Open exam record in new window
                    const newWindow = window.open('', '_blank');
                    newWindow.document.write(result.html);
                    newWindow.document.close();
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                console.error('Error viewing exam record:', error);
                showMessage('error', 'Σφάλμα προβολής πρακτικού');
            }
        }

        // Show message
        function showMessage(type, message) {
            const existingMsg = document.querySelector('.message');
            if (existingMsg) existingMsg.remove();

            const msgDiv = document.createElement('div');
            msgDiv.className = `message ${type}`;
            msgDiv.textContent = message;

            const activeSection = document.querySelector('.section.active .section-content');
            if (activeSection) {
                activeSection.insertBefore(msgDiv, activeSection.firstChild);
            }

            setTimeout(() => msgDiv.remove(), 5000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Student dashboard loaded successfully');
        });
    </script>
</body>
</html>
