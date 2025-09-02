<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') 
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
$secretary_name = $user_data['name'] . ' ' . $user_data['surname'];
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard - Διπλωματικές Εργασίες</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
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
            border-color: #e91e63;
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

        .footer {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
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

        .thesis-box {
            border: 1px solid #e1e8ed;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            background: #fafafa;
            transition: all 0.3s ease;
        }

        .thesis-box:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .thesis-box h3 {
            color: #2c3e50;
            cursor: pointer;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .thesis-details {
            margin-top: 15px;
            font-size: 14px;
            line-height: 1.8;
            color: #555;
        }

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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #e91e63;
        }

        .submit-btn {
            background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(233, 30, 99, 0.3);
        }

        .output {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #e91e63;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 13px;
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
            
            .welcome-section,
            .section-content {
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
                <h1> Σύστημα Διπλωματικών - Γραμματεία</h1>
            </div>
            <div class="user-info">
                <span class="user-name">Γραμματέας: <span id="username"><?php echo htmlspecialchars($secretary_name); ?></span></span>
                <a href="login.html" class="logout-btn">Αποσύνδεση</a>
            </div>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div id="dashboard" class="main-container">
        <div class="welcome-section">
            <h2>Καλώς ήρθατε!</h2>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card" onclick="showSection('thesesSection')">
                <div class="card-title">Προβολή Διπλωματικών</div>
                <div class="card-description">Δείτε όλες τις διπλωματικές εργασίες που βρίσκονται σε εξέλιξη και τις λεπτομέρειές τους.</div>
            </div>

            <div class="dashboard-card" onclick="showSection('importSection')">
                <div class="card-title">Εισαγωγή Δεδομένων</div>
                <div class="card-description">Εισάγετε δεδομένα από JSON αρχεία για καθηγητές και φοιτητές στο σύστημα.</div>
            </div>

            <div class="dashboard-card" onclick="showSection('manageSection')">
                <div class="card-title">Διαχείριση Διπλωματικών</div>
                <div class="card-description">Διαχειριστείτε την κατάσταση των διπλωματικών εργασιών και ενημερώστε τα στοιχεία τους.</div>
            </div>
        </div>
    </div>

    <!-- Theses Section -->
    <div id="thesesSection" class="section">
        <div class="section-content">
            <button class="back-btn" onclick="showDashboard()">← Επιστροφή στο Dashboard</button>
            <h2>Προβολή Διπλωματικών Εργασιών</h2>
            <div id="thesesDisplay"></div>
        </div>
    </div>

    <!-- Import Section -->
    <div id="importSection" class="section">
        <div class="section-content">
            <button class="back-btn" onclick="showDashboard()">← Επιστροφή στο Dashboard</button>
            <h2>Εισαγωγή Δεδομένων από JSON</h2>
            <div class="form-group">
                <label for="jsonFile">Επιλέξτε JSON αρχείο:</label>
                <input type="file" id="jsonFile" accept=".json" required>
            </div>
            <button class="submit-btn" onclick="submitJson()">Εισαγωγή Δεδομένων</button>
            <div id="jsonOutput" class="output" style="display: none;"></div>
        </div>
    </div>

    <!-- Manage Section -->
    <div id="manageSection" class="section">
        <div class="section-content">
            <button class="back-btn" onclick="showDashboard()">← Επιστροφή στο Dashboard</button>
            <h2>Διαχείριση Διπλωματικών Εργασιών</h2>
            <div class="form-group">
                <label for="diplomaSelect">Επιλέξτε Διπλωματική:</label>
                <select id="diplomaSelect" onchange="renderManagementForm()">
                    <option value="">-- Επιλέξτε διπλωματική --</option>
                </select>
            </div>
            <div id="managementFormContainer"></div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ΤΜΗΥΠ - Σύστημα Διαχείρισης Διπλωματικών Εργασιών</p>
    </div>

    <script>
        // Show/Hide sections
        function showSection(sectionId) {
            // Hide dashboard
            document.getElementById('dashboard').style.display = 'none';
            
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Load section-specific data
            if (sectionId === 'thesesSection') {
                loadTheses();
            } else if (sectionId === 'manageSection') {
                loadDiplomas();
            }
        }

        function showDashboard() {
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));
            
            // Show dashboard
            document.getElementById('dashboard').style.display = 'block';
        }

        function toggleDetails(id) {
            const details = document.getElementById(id);
            details.style.display = (details.style.display === "none") ? "block" : "none";
        }

        async function fetchJson(url) {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        }

        // ================= Load Theses =================
        async function loadTheses() {
            const section = document.getElementById("thesesDisplay");
            section.innerHTML = '<p>Φόρτωση διπλωματικών...</p>';

            try {
                const data = await fetchJson('../secretary/1getTheses.php');
                const filtered = data.filter(d => d.status == 2 || d.status == 3);

                if (filtered.length === 0) {
                    section.innerHTML = '<p>Δεν υπάρχουν διαθέσιμες διπλωματικές.</p>';
                    return;
                }

                section.innerHTML = '';
                filtered.forEach((dipl, idx) => {
                    const statusText = dipl.status == 2 ? 'Ενεργή' : 'Υπό εξέταση';
                    let timePassed = '—';

                    if (dipl.assignment_date) {
                        const assigned = new Date(dipl.assignment_date);
                        const diffDays = Math.floor((Date.now() - assigned) / (1000*60*60*24));
                        const months = Math.floor(diffDays / 30);
                        const years = Math.floor(diffDays / 365);

                        timePassed = years > 0
                          ? `${years} έτος/η και ${months % 12} μήνες`
                          : months > 0
                          ? `${months} μήνες και ${diffDays % 30} μέρες`
                          : `${diffDays} μέρες`;
                    }

                    const div = document.createElement("div");
                    div.className = "thesis-box";
                    const detailsId = `thesis-${idx}`;

                    div.innerHTML = `
                        <h3 onclick="toggleDetails('${detailsId}')">${dipl.title} 📝</h3>
                        <div id="${detailsId}" class="thesis-details" style="display:none;">
                            <p><strong>Περιγραφή:</strong> ${dipl.description}</p>
                            <p><strong>ΑΜ Φοιτητή:</strong> ${dipl.student_am ?? '—'}</p>
                            <p><strong>Επιβλέπων:</strong> ${dipl.supervisor_name} ${dipl.supervisor_surname}</p>
                            <p><strong>Επιτροπή:</strong> 
                                ${dipl.committee1_name || '—'} ${dipl.committee1_surname || ''},
                                ${dipl.committee2_name || '—'} ${dipl.committee2_surname || ''}
                            </p>
                            <p><strong>Ανάθεση πριν:</strong> ${timePassed}</p>
                            <p><strong>Κατάσταση:</strong> <span style="color: ${dipl.status == 2 ? '#27ae60' : '#f39c12'}; font-weight: bold;">${statusText}</span></p>
                        </div>`;

                    section.appendChild(div);
                });
            } catch (err) {
                console.error(err);
                section.innerHTML = "<p style='color: #e74c3c;'>❌ Αποτυχία φόρτωσης δεδομένων.</p>";
            }
        }

        // ================= Import JSON =================
        function submitJson() {
            const fileInput = document.getElementById('jsonFile');
            const output = document.getElementById('jsonOutput');

            if (!fileInput.files.length) {
                output.innerHTML = "⚠️ Παρακαλώ επιλέξτε αρχείο JSON.";
                output.style.display = 'block';
                output.style.color = "#e74c3c";
                return;
            }

            const reader = new FileReader();
            reader.onload = async e => {
                try {
                    const data = JSON.parse(e.target.result);
                    if (!data.professors || !data.students) {
                        throw new Error("Λείπουν τα πεδία 'professors' και 'students'");
                    }

                    output.innerHTML = "⏳ Επεξεργασία δεδομένων...";
                    output.style.display = 'block';
                    output.style.color = "#2c3e50";

                    const res = await fetch('../secretary/2postJson.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await res.json();
                    if (!result.success) throw new Error(result.error || "Άγνωστο σφάλμα");

                    output.innerHTML = `✅ Επιτυχής εισαγωγή!
Καθηγητές: ${result.stats.professors_imported}
Φοιτητές: ${result.stats.students_imported}`;
                    output.style.color = "#27ae60";
                } catch (err) {
                    console.error(err);
                    output.innerHTML = "❌ Σφάλμα: " + err.message;
                    output.style.color = "#e74c3c";
                    output.style.display = 'block';
                }
            };
            reader.readAsText(fileInput.files[0]);
        }

        // ================= Manage Theses =================
        async function loadDiplomas() {
            try {
                const response = await fetchJson('../secretary/3getTheses.php');
                const select = document.getElementById('diplomaSelect');

                select.innerHTML = '<option value="">-- Επιλέξτε διπλωματική --</option>';
                const diplomas = response.success ? response.data : [];

                diplomas.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.ID;
                    opt.textContent = `${d.title} (${d.status == 2 ? 'Ενεργή' : 'Υπό Εξέταση'})`;
                    opt.dataset.status = d.status;
                    select.appendChild(opt);
                });
            } catch (err) {
                console.error(err);
                alert('Σφάλμα φόρτωσης διπλωματικών.');
            }
        }

        function renderManagementForm() {
            const select = document.getElementById('diplomaSelect');
            const option = select.options[select.selectedIndex];
            const status = option?.dataset.status;
            const id = select.value;
            const form = document.getElementById('managementFormContainer');
            form.innerHTML = '';

            if (!id) return;

            if (status == 2) {
                form.innerHTML = `
                    <h3 style="margin: 30px 0 20px 0; color: #2c3e50;">Ενέργειες για Ενεργή Διπλωματική</h3>
                    <div class="form-group">
                        <label>ΑΠ απόφαση ΓΣ ανάθεσης:</label>
                        <input type="number" id="gsApprovalNumber" required>
                    </div>
                    <div class="form-group">
                        <label>Ακύρωση Ανάθεσης;</label>
                        <select id="cancelOption" onchange="toggleCancelFields()">
                            <option value="no">Όχι</option>
                            <option value="yes">Ναι</option>
                        </select>
                    </div>
                    <div id="cancelFields" style="display:none;">
                        <div class="form-group">
                            <label>ΑΠ απόφαση ΓΣ ακύρωσης:</label>
                            <input type="number" id="gsCancelNumber">
                        </div>
                        <div class="form-group">
                            <label>Λόγος ακύρωσης:</label>
                            <textarea id="cancelReason" rows="3">Κατόπιν αίτησης Φοιτητή/τριας</textarea>
                        </div>
                    </div>
                    <button class="submit-btn" onclick="submitActive(${id})">Υποβολή Αλλαγών</button>`;
            }

            if (status == 3) {
                form.innerHTML = `
                    <h3 style="margin: 30px 0 20px 0; color: #2c3e50;">Ενέργειες για Διπλωματική Υπό Εξέταση</h3>
                    <p style="margin-bottom: 20px; color: #7f8c8d;">Αλλάξτε την κατάσταση της διπλωματικής σε "Περατωμένη".</p>
                    <button class="submit-btn" onclick="submitReview(${id})">Μετάβαση σε Περατωμένη</button>`;
            }
        }

        function toggleCancelFields() {
            const cancel = document.getElementById('cancelOption').value;
            const fields = document.getElementById('cancelFields');
            const approvalInput = document.getElementById('gsApprovalNumber');
            
            fields.style.display = cancel === 'yes' ? 'block' : 'none';
            approvalInput.disabled = (cancel === 'yes');
        }

        async function submitActive(id) {
            const cancel = document.getElementById('cancelOption').value;
            const year = new Date().getFullYear();

            const payload = { thesis_id: id, cancel: cancel === 'yes' };

            if (cancel === 'yes') {
                payload.gs_cancel_number = document.getElementById('gsCancelNumber').value.trim();
                payload.gs_cancel_year = year;
                payload.reason = document.getElementById('cancelReason').value.trim();
            } else {
                payload.gs_approval_number = document.getElementById('gsApprovalNumber').value.trim();
                payload.gs_approval_year = year;
            }

            try {
                const res = await fetch('../secretary/3manageActiveThesis.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const response = await res.json();
                alert('✅ ' + response.message);
                await loadDiplomas();
                document.getElementById('managementFormContainer').innerHTML = '';
            } catch (err) {
                console.error(err);
                alert("❌ Σφάλμα υποβολής.");
            }
        }

        async function submitReview(id) {
            try {
                const res = await fetch('../secretary/3manageUnderReviewThesis.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ thesis_id: id })
                });
                const response = await res.json();
                alert('✅ ' + (response.message || "Ενημερώθηκε επιτυχώς."));
                await loadDiplomas();
                document.getElementById('managementFormContainer').innerHTML = '';
            } catch (err) {
                console.error(err);
                alert("❌ Σφάλμα υποβολής.");
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Secretary dashboard loaded successfully');
        });
    </script>
</body>
</html>