<?php
require_once __DIR__ . '/../auth/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$logged = isset($_SESSION['discord']) && isset($_SESSION['discord']['id']);
$allowed = false;
if ($logged && !empty($ALLOWED_DISCORD_IDS)) {
    $allowed = in_array((string)$_SESSION['discord']['id'], $ALLOWED_DISCORD_IDS, true);
}
if (!$logged || !$allowed) {
    header('Location: /index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin — HCR2 Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
      body { font-family: Arial, sans-serif; background:#f4f4f9; color:#333; padding:20px; }
      h1 { color:#007bff; }
      .form-container { background:#fff; padding:20px; border-radius:8px; max-width:900px; margin:20px auto; box-shadow:0 2px 6px rgba(0,0,0,0.08); }
      input, select, button { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:6px; }
      button { background:#007bff; color:#fff; border:none; cursor:pointer; }
      .topbar { display:flex; justify-content:space-between; align-items:center; max-width:900px; margin:0 auto 20px; }
      .topbar a { text-decoration:none; color:#007bff; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Admin Panel — Edit/Add/Delete Records</h1>
        <div>
            <span>Logged in as <?php echo htmlspecialchars($_SESSION['discord']['username'] ?? $_SESSION['discord']['id']); ?></span>
            &nbsp;|&nbsp;<a href="/auth/logout.php">Logout</a>
            &nbsp;|&nbsp;<a href="/index.html">Back to Public</a>
        </div>
    </div>

    <div class="form-container">
        <h2>Submit a New Record ✅</h2>
        <form id="record-form" onsubmit="submitRecord(event)">
            <label>Map</label>
            <select id="map-select" required></select>
            <label>Vehicle</label>
            <select id="vehicle-select" required></select>
            <label>Distance</label>
            <input type="number" id="distance-input" required>
            <label>Existing Player</label>
            <input type="text" id="player-filter" placeholder="Filter players..." oninput="filterPlayers()">
            <select id="player-select" onchange="handlePlayerSelection()"><option value="">Select existing player</option></select>
            <label>Or add new player</label>
            <input type="text" id="new-player-input" oninput="newPlayerTyped()">
            <label>Country</label>
            <input type="text" id="country-input">
            <button type="submit">Submit Record</button>
        </form>
        <p id="form-message"></p>
    </div>

    <div class="form-container">
        <h2>Delete a Record ❌</h2>
        <form id="delete-form" onsubmit="deleteRecord(event)">
            <label>Record</label>
            <select id="record-select" required><option value="">Select a record</option></select>
            <button type="submit">Delete Record</button>
        </form>
        <p id="delete-message"></p>
    </div>

    <div class="form-container" id="pending-submissions-container">
        <h2>Pending Submissions (from users)</h2>
        <div id="pending-list">Loading...</div>
    </div>

    <div class="form-container">
        <h2>Site News (Admins)</h2>
        <form id="news-form" onsubmit="postNews(event)">
            <label>Title</label>
            <input type="text" id="news-title-input" required>
            <label>Content</label>
            <textarea id="news-content-input" rows="6" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;" required></textarea>
            <div style="display:flex; gap:8px; margin-top:12px;"><button type="submit">Post News</button><button type="button" onclick="loadAdminNews()" style="background:#ccc;color:#000;">Refresh</button></div>
        </form>
        <p id="news-message"></p>
        <h3 style="margin-top:18px;">Recent News</h3>
        <div id="admin-news-list">Loading...</div>
    </div>

    <div class="form-container">
        <h2>Database & Backups</h2>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="/auth/admin_actions.php?action=download_db" style="display:inline-block; padding:10px 14px; background:#007bff; color:#fff; border-radius:6px; text-decoration:none;">Download DB</a>
            <button type="button" onclick="createBackup()">Create Backup</button>
            <button type="button" onclick="listBackups()" style="background:#ccc;color:#000;">List Backups</button>
            <button type="button" onclick="runIntegrity()" style="background:#28a745;color:#fff;">Integrity Check</button>
        </div>
        <div id="backups-list" style="margin-top:12px;">Backups will appear here.</div>

        <h3 style="margin-top:12px;">Import SQL</h3>
        <form id="import-form" onsubmit="importSQL(event)" enctype="multipart/form-data">
            <input type="file" name="sqlfile" accept=".sql" required>
            <div style="display:flex; gap:8px; margin-top:8px;"><button type="submit">Import SQL</button></div>
            <p id="import-message"></p>
        </form>
    </div>

<script>
function esc(input) {
    if (input === null || input === undefined) return '';
    return String(input)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

let allPlayers = [];

async function fetchJSON(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    return res.json();
}

function populateFormOptions() {
    fetchJSON('/php/load_data.php?type=maps').then(data => {
        const sel = document.getElementById('map-select');
        sel.innerHTML = '<option value="">Select a Map</option>';
        (data || []).forEach(m => sel.appendChild(new Option(m.nameMap, m.idMap)));
    });
    fetchJSON('/php/load_data.php?type=vehicles').then(data => {
        const sel = document.getElementById('vehicle-select');
        sel.innerHTML = '<option value="">Select a Vehicle</option>';
        (data || []).forEach(v => sel.appendChild(new Option(v.nameVehicle, v.idVehicle)));
    });
    fetchJSON('/php/load_data.php?type=players').then(data => {
        allPlayers = data || [];
        const sel = document.getElementById('player-select');
        sel.innerHTML = '<option value="">Select existing player</option>';
        allPlayers.forEach(p => sel.appendChild(new Option(p.namePlayer, p.idPlayer)));
    });
    populateDeleteOptions();
}

function populateDeleteOptions() {
    fetchJSON('/php/load_data.php?type=records').then(data => {
        const sel = document.getElementById('record-select');
        sel.innerHTML = '<option value="">Select a record</option>';
        (data || []).forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.idRecord;
            opt.textContent = `${r.distance} - ${r.map_name} - ${r.vehicle_name} - ${r.player_name}`;
            sel.appendChild(opt);
        });
    });
}

function filterPlayers() {
    const q = document.getElementById('player-filter').value.toLowerCase();
    const sel = document.getElementById('player-select');
    sel.innerHTML = '<option value="">Select existing player</option>';
    allPlayers.filter(p => p.namePlayer.toLowerCase().includes(q)).forEach(p => sel.appendChild(new Option(p.namePlayer, p.idPlayer)));
}

function handlePlayerSelection() {
    const pid = document.getElementById('player-select').value;
    const countryInput = document.getElementById('country-input');
    const newPlayerInput = document.getElementById('new-player-input');
    if (pid) {
        const p = allPlayers.find(x => String(x.idPlayer) === String(pid));
        countryInput.value = p ? (p.country || '') : '';
        countryInput.disabled = true;
        newPlayerInput.value = '';
    } else {
        countryInput.value = '';
        countryInput.disabled = false;
    }
}

function newPlayerTyped() {
    if (document.getElementById('new-player-input').value.trim() !== '') {
        document.getElementById('player-select').value = '';
        document.getElementById('country-input').disabled = false;
    }
}

function submitRecord(e) {
    e.preventDefault();
    const mapId = document.getElementById('map-select').value;
    const vehicleId = document.getElementById('vehicle-select').value;
    const distance = document.getElementById('distance-input').value;
    const playerId = document.getElementById('player-select').value;
    const newPlayerName = document.getElementById('new-player-input').value;
    const country = document.getElementById('country-input').value;
    const selectedPlayerOption = document.getElementById('player-select').selectedOptions[0];
    const selectedPlayerName = selectedPlayerOption ? selectedPlayerOption.textContent : '';

    if (!playerId && !newPlayerName) {
        showFormMessage('Please select an existing player or add a new one.', true);
        return;
    }
    if (!playerId && newPlayerName && !country) {
        showFormMessage('Please provide a country for the new player.', true);
        return;
    }

    const hasPlayerId = (playerId !== null && playerId !== undefined && playerId !== '');
    const formData = hasPlayerId ? { mapId, vehicleId, distance, playerId, playerName: selectedPlayerName } : { mapId, vehicleId, distance, playerId: null, newPlayerName, country };

    fetch('/php/submit_record.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(formData)
    }).then(async resp => {
        const data = await resp.json().catch(()=>({ error: 'Invalid server response' }));
        if (!resp.ok) {
            showFormMessage(data.error || 'Server error', true);
            return;
        }
        if (data.success) {
            const msg = `✅ Record submitted! | ${data.playerName || 'Unknown'} | ${data.mapName || 'Unknown'} | ${data.vehicleName || 'Unknown'} | ${data.distance || '?'}m`;
            showFormMessage(msg, false);
            document.getElementById('record-form').reset();
            populateFormOptions();
            populateDeleteOptions();
            setTimeout(() => document.getElementById('form-message').textContent = '', 5000);
        } else {
            showFormMessage(data.error || 'Unknown error', true);
        }
    }).catch(()=> showFormMessage('Error submitting record.', true));
}

function deleteRecord(e) {
    e.preventDefault();
    const recordId = document.getElementById('record-select').value;
    if (!recordId) {
        showDeleteMessage('Please select a record to delete.', true);
        return;
    }
    fetch('/php/delete_record.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ recordId })
    }).then(async resp => {
        const data = await resp.json().catch(()=>({ error: 'Invalid server response' }));
        if (!resp.ok) {
            showDeleteMessage(data.error || 'Server error', true);
            return;
        }
        if (data.success) {
            showDeleteMessage('Record deleted successfully!', false);
            populateDeleteOptions();
        } else {
            showDeleteMessage(data.error || 'Unknown error', true);
        }
    }).catch(()=> showDeleteMessage('Error deleting record.', true));
}

function showFormMessage(msg, isError) {
    const el = document.getElementById('form-message');
    el.textContent = msg;
    el.style.color = isError ? 'red' : 'green';
}
function showDeleteMessage(msg, isError) {
    const el = document.getElementById('delete-message');
    el.textContent = msg;
    el.style.color = isError ? 'red' : 'green';
}

async function loadPendingSubmissions() {
    const container = document.getElementById('pending-list');
    if (!container) return;
    container.textContent = 'Loading...';
    try {
        const res = await fetch('/auth/admin_pending.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (data.error) {
            container.textContent = 'Error: ' + data.error;
            return;
        }
        const pending = data.pending || [];
        if (pending.length === 0) {
            container.textContent = 'No pending submissions.';
            return;
        }
        let html = '<table style="width:100%; border-collapse: collapse;"><tr><th>ID</th><th>Map</th><th>Vehicle</th><th>Distance</th><th>Player</th><th>Country</th><th>When</th><th>Actions</th></tr>';
        pending.forEach(p => {
            const mapLabel = p.mapName ? p.mapName : p.idMap;
            const vehicleLabel = p.vehicleName ? p.vehicleName : p.idVehicle;
            html += `<tr style="border-top:1px solid #eee;"><td>${p.id}</td><td>${mapLabel}</td><td>${vehicleLabel}</td><td>${p.distance}</td><td>${p.playerName}</td><td>${p.playerCountry}</td><td>${p.submitted_at}</td><td><button onclick="approveSubmission(${p.id})">Approve</button> <button onclick="rejectSubmission(${p.id})" style="background:#ccc;color:#000;">Reject</button></td></tr>`;
        });
        html += '</table>';
        container.innerHTML = html;
    } catch (err) {
        console.error('Failed to load pending submissions', err);
        container.textContent = 'Failed to load pending submissions.';
    }
}

async function approveSubmission(id) {
    if (!confirm('Approve submission #' + id + '? This will replace the existing record for the same map/vehicle.')) return;
    try {
        const res = await fetch('/auth/admin_pending.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'approve', id }) });
        const data = await res.json();
        if (data.success) {
            loadPendingSubmissions();
            populateDeleteOptions();
            populateFormOptions();
            alert('Submission approved.');
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    } catch (err) {
        console.error(err);
        alert('Request failed');
    }
}

async function rejectSubmission(id) {
    if (!confirm('Reject submission #' + id + '?')) return;
    try {
        const res = await fetch('/auth/admin_pending.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'reject', id }) });
        const data = await res.json();
        if (data.success) {
            loadPendingSubmissions();
            alert('Submission rejected.');
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    } catch (err) {
        console.error(err);
        alert('Request failed');
    }
}

window.onload = () => {
    populateFormOptions();
    loadPendingSubmissions();
    loadAdminNews();
    listBackups();
};

async function postNews(e) {
    e.preventDefault();
    const title = document.getElementById('news-title-input').value.trim();
    const content = document.getElementById('news-content-input').value.trim();
    const msgEl = document.getElementById('news-message');
    if (!title || !content) {
        if (msgEl) { msgEl.textContent = 'Please provide both title and content.'; msgEl.style.color = 'red'; }
        return;
    }
    try {
        const res = await fetch('/auth/post_news.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ title, content }) });
        const data = await res.json();
        if (!res.ok) {
            if (msgEl) { msgEl.textContent = data.error || 'Posting failed'; msgEl.style.color = 'red'; }
            return;
        }
        if (data.success) {
            if (msgEl) { msgEl.textContent = 'News posted.'; msgEl.style.color = 'green'; }
            document.getElementById('news-form').reset();
            loadAdminNews();
            setTimeout(()=>{ msgEl.textContent = ''; }, 3000);
        } else {
            if (msgEl) { msgEl.textContent = data.error || 'Unknown error'; msgEl.style.color = 'red'; }
        }
    } catch (err) {
        console.error('Post news failed', err);
        if (msgEl) { msgEl.textContent = 'Network error posting news.'; msgEl.style.color = 'red'; }
    }
}

async function loadAdminNews() {
    const el = document.getElementById('admin-news-list');
    if (!el) return;
    el.textContent = 'Loading...';
    try {
        const res = await fetch('/php/get_news.php?limit=20', { credentials: 'same-origin' });
        const data = await res.json();
        if (data.error) { el.textContent = 'Error: ' + data.error; return; }
        const items = Array.isArray(data.news) ? data.news : [];
        if (items.length === 0) { el.textContent = 'No news yet.'; return; }
        let html = '<ul style="list-style:none;padding:0;margin:0;">';
        items.forEach(n => {
            html += `<li style="padding:8px;border-bottom:1px solid #eee;"><strong>${esc(n.title)}</strong><div style="font-size:12px;color:#666;margin:6px 0;">${n.created_at} — ${esc(n.author||'')}</div><div style="white-space:pre-wrap;">${esc(n.content)}</div></li>`;
        });
        html += '</ul>';
        el.innerHTML = html;
    } catch (err) {
        console.error('Load admin news failed', err);
        el.textContent = 'Failed to load news.';
    }
}

async function createBackup() {
    try {
        const res = await fetch('/auth/admin_actions.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'create_backup' }) });
        const data = await res.json();
        if (data.success) {
            alert('Backup created: ' + data.filename);
            listBackups();
        } else {
            alert('Failed to create backup: ' + (data.error || 'unknown'));
        }
    } catch (err) { console.error(err); alert('Request failed'); }
}

async function listBackups() {
    const el = document.getElementById('backups-list');
    if (!el) return;
    el.textContent = 'Loading...';
    try {
        const res = await fetch('/auth/admin_actions.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'list_backups' }) });
        const data = await res.json();
        const list = Array.isArray(data.backups) ? data.backups : [];
        if (list.length === 0) { el.textContent = 'No backups found.'; return; }
        let html = '<table style="width:100%; border-collapse: collapse;"><tr><th>Name</th><th>Size</th><th>Modified</th><th>Actions</th></tr>';
        list.forEach(b => {
            html += `<tr style="border-top:1px solid #eee;"><td>${b.name}</td><td>${b.size}</td><td>${b.mtime}</td><td><button onclick="restoreBackup('${b.name}')">Restore</button> <button onclick="deleteBackup('${b.name}')" style="background:#ccc;color:#000;">Delete</button></td></tr>`;
        });
        html += '</table>';
        el.innerHTML = html;
    } catch (err) { console.error(err); el.textContent = 'Failed to load backups.'; }
}

async function deleteBackup(name) {
    if (!confirm('Delete backup ' + name + '?')) return;
    try {
        const res = await fetch('/auth/admin_actions.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'delete', filename: name }) });
        const data = await res.json();
        if (data.success) { listBackups(); } else { alert('Delete failed: ' + (data.error||'unknown')); }
    } catch (err) { console.error(err); alert('Request failed'); }
}

async function restoreBackup(name) {
    if (!confirm('Restore backup ' + name + '? This will replace the current DB.')) return;
    try {
        const res = await fetch('/auth/admin_actions.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'restore', filename: name }) });
        const data = await res.json();
        if (data.success) { alert('Restored.'); listBackups(); } else { alert('Restore failed: ' + (data.error||'unknown')); }
    } catch (err) { console.error(err); alert('Request failed'); }
}

async function importSQL(e) {
    e.preventDefault();
    const msgEl = document.getElementById('import-message');
    msgEl.textContent = 'Uploading...';
    const form = document.getElementById('import-form');
    const fd = new FormData(form);
    fd.append('action', 'import');
    try {
        const res = await fetch('/auth/admin_actions.php', { method: 'POST', credentials: 'same-origin', body: fd });
        const data = await res.json();
        if (data.success) { msgEl.textContent = 'Import successful.'; listBackups(); } else { msgEl.textContent = 'Import failed: ' + (data.error||'unknown'); }
    } catch (err) { console.error(err); msgEl.textContent = 'Request failed.'; }
}

async function runIntegrity() {
    try {
        const res = await fetch('/auth/admin_actions.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'integrity' }) });
        const data = await res.json();
        alert('Integrity check result: ' + (data.result || JSON.stringify(data)));
    } catch (err) { console.error(err); alert('Request failed'); }
}
</script>
</body>
</html>
