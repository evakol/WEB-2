<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'secretary') {
    header("Location: login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .logout-btn {
            display: inline-block;
            background-color: #e52d27;
            color: white;
            font-size: 14px;
            padding: 8px 16px;
            margin: 10px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: center;
            font-family: inherit;
        }
    </style>
</head>

<body class="body-template-2">

    <div class="dashboard-container" id="dashboard">
        <div class="form-group">
            <h1>Πίνακας ελέγχου γραμματέα</h1>
            <a href="login.html" class="logout-btn">Αποσύνδεση</a>
        </div>

        <!-- Menu Options -->
        <button class="menu-btn" onclick="showTheses()">Προβολή διπλωματικής</button>
        <button class="menu-btn" onclick="showImportSection()">Εισαγωγή δεδομένων</button>
        <button class="menu-btn" onclick="showManageSection()">Διαχείριση διπλωματικής</button>

        <div class="footer">
            <p>&copy; 2025 Secretary Dashboard</p>
        </div>
    </div>

    <!-- 1. View Theses -->
    <div class="thesis-section" id="thesisSection" style="display:none;">
        <h1>Προβολή διπλωματικών</h1>
        <div id="thesisDisplaySection" class="thesis-display"></div>
        <button onclick="goBack('thesisSection')">Επιστροφή</button>
    </div>

    <!-- 2. Import JSON -->
    <div class="import-section" id="importSection" style="display:none;">
        <h1>Εισαγωγή αρχείου JSON</h1>
        <input type="file" id="jsonFileInput" accept=".json" required>
        <button onclick="submitJson()">Υποβολή</button>
        <button onclick="goBack('importSection')">Επιστροφή</button>
        <pre class="output" id="jsonOutput"></pre>
    </div>

    <!-- 3. Manage Thesis -->
    <div class="manage-section" id="manageSection" style="display:none;">
        <h1>Διαχείριση Διπλωματικής</h1>

        <label for="diplomaSelect">Επιλέξτε Διπλωματική:</label>
        <select id="diplomaSelect" onchange="renderManagementForm()">
            <option value="">-- Επιλογή --</option>
        </select>

        <div id="managementFormContainer"></div>

        <button onclick="goBack('manageSection')">Επιστροφή</button>
    </div>

</body>

<script>
// ================= Utility =================
function goBack(sectionId) {
    document.getElementById(sectionId).style.display = 'none';
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

// ================= 1. View Theses =================
async function showTheses() {
    document.getElementById('dashboard').style.display = 'none';
    document.getElementById('thesisSection').style.display = 'block';

    const section = document.getElementById("thesisDisplaySection");
    section.innerHTML = '';

    try {
        const data = await fetchJson('../secretary/1getTheses.php');
        const filtered = data.filter(d => d.status == 2 || d.status == 3);

        if (filtered.length === 0) {
            section.innerHTML = '<p>Δεν υπάρχουν διαθέσιμες διπλωματικές.</p>';
            return;
        }

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
                <h3 style="cursor:pointer;" onclick="toggleDetails('${detailsId}')">${dipl.title}</h3>
                <div id="${detailsId}" class="thesis-details" style="display:none;">
                    <p><strong>Περιγραφή:</strong> ${dipl.description}</p>
                    <p><strong>ΑΜ Φοιτητή:</strong> ${dipl.student_am ?? '—'}</p>
                    <p><strong>Επιβλέπων:</strong> ${dipl.supervisor_name} ${dipl.supervisor_surname}</p>
                    <p><strong>Επιτροπή:</strong> 
                        ${dipl.committee1_name || '—'} ${dipl.committee1_surname || ''},
                        ${dipl.committee2_name || '—'} ${dipl.committee2_surname || ''}
                    </p>
                    <p><strong>Ανάθεση πριν:</strong> ${timePassed}</p>
                    <p><strong>Κατάσταση:</strong> ${statusText}</p>
                </div>`;

            section.appendChild(div);
        });
    } catch (err) {
        console.error(err);
        section.innerHTML = "<p>Αποτυχία φόρτωσης δεδομένων.</p>";
    }
}

// ================= 2. Import JSON =================
function showImportSection() {
    document.getElementById('dashboard').style.display = 'none';
    document.getElementById('importSection').style.display = 'block';
}

function submitJson() {
    const fileInput = document.getElementById('jsonFileInput');
    const output = document.getElementById('jsonOutput');

    if (!fileInput.files.length) {
        output.textContent = "⚠️ Παρακαλώ επιλέξτε αρχείο JSON.";
        output.style.color = "#c0392b";
        return;
    }

    const reader = new FileReader();
    reader.onload = async e => {
        try {
            const data = JSON.parse(e.target.result);
            if (!data.professors || !data.students) throw new Error("Λείπουν τα πεδία 'professors' και 'students'");

            const res = await fetch('../secretary/2postJson.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await res.json();
            if (!result.success) throw new Error(result.error || "Άγνωστο σφάλμα");

            output.textContent = `✅ Επιτυχής εισαγωγή!\nΚαθηγητές: ${result.stats.professors_imported}\nΦοιτητές: ${result.stats.students_imported}`;
            output.style.color = "#2c3e50";
        } catch (err) {
            console.error(err);
            output.textContent = "❌ Σφάλμα: " + err.message;
            output.style.color = "#c0392b";
        }
    };
    reader.readAsText(fileInput.files[0]);
}

// ================= 3. Manage Theses =================
async function showManageSection() {
    document.getElementById('dashboard').style.display = 'none';
    document.getElementById('manageSection').style.display = 'flex';
    await loadDiplomas();
}

async function loadDiplomas() {
    try {
        const response = await fetchJson('../secretary/3getTheses.php');
        const select = document.getElementById('diplomaSelect');

        select.innerHTML = '<option value="">-- Επιλογή --</option>';
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
            <h3>Ενέργειες για Διπλωματική (Ενεργή)</h3>
            <label>ΑΠ απόφαση ΓΣ ανάθεσης:</label>
            <input type="number" id="gsApprovalNumber" required>
            <label>Ακύρωση Ανάθεσης;</label>
            <select id="cancelOption" onchange="toggleCancelFields()">
                <option value="no">Όχι</option>
                <option value="yes">Ναι</option>
            </select>
            <div id="cancelFields" style="display:none;">
                <label>ΑΠ απόφαση ΓΣ ακύρωσης:</label>
                <input type="number" id="gsCancelNumber">
                <label>Λόγος ακύρωσης:</label>
                <textarea id="cancelReason">Κατόπιν αίτησης Φοιτητή/τριας</textarea>
            </div>
            <button onclick="submitActive(${id})">Υποβολή</button>`;
    }

    if (status == 3) {
        form.innerHTML = `
            <h3>Ενέργειες για Διπλωματική (Υπό Εξέταση)</h3>
            <button onclick="submitReview(${id})">Αλλαγή κατάστασης σε Περατωμένη</button>`;
    }
}

function toggleCancelFields() {
    const cancel = document.getElementById('cancelOption').value;
    document.getElementById('cancelFields').style.display = cancel === 'yes' ? 'block' : 'none';
    document.getElementById('gsApprovalNumber').disabled = (cancel === 'yes');
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
        alert(response.message);
        await loadDiplomas();
        document.getElementById('managementFormContainer').innerHTML = '';
    } catch (err) {
        console.error(err);
        alert("Σφάλμα υποβολής.");
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
        alert(response.message || "Ενημερώθηκε επιτυχώς.");
        await loadDiplomas();
        document.getElementById('managementFormContainer').innerHTML = '';
    } catch (err) {
        console.error(err);
        alert("Σφάλμα υποβολής.");
    }
}
</script>
</html>
