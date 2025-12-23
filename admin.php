<?php
require_once __DIR__ . '/auth/config.php';
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

<script>
let allPlayers = [];

async function fetchJSON(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    return res.json();
}

function populateFormOptions() {
    fetchJSON('load_data.php?type=maps').then(data => {
        const sel = document.getElementById('map-select');
        sel.innerHTML = '<option value="">Select a Map</option>';
        (data || []).forEach(m => sel.appendChild(new Option(m.nameMap, m.idMap)));
    });
    fetchJSON('load_data.php?type=vehicles').then(data => {
        const sel = document.getElementById('vehicle-select');
        sel.innerHTML = '<option value="">Select a Vehicle</option>';
        (data || []).forEach(v => sel.appendChild(new Option(v.nameVehicle, v.idVehicle)));
    });
    fetchJSON('load_data.php?type=players').then(data => {
        allPlayers = data || [];
        const sel = document.getElementById('player-select');
        sel.innerHTML = '<option value="">Select existing player</option>';
        allPlayers.forEach(p => sel.appendChild(new Option(p.namePlayer, p.idPlayer)));
    });
    populateDeleteOptions();
}

function populateDeleteOptions() {
    fetchJSON('load_data.php?type=records').then(data => {
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

    fetch('submit_record.php', {
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
            // Garder le message visible pendant 5 secondes
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
    fetch('delete_record.php', {
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

window.onload = () => {
    populateFormOptions();
};
</script>
</body>
</html>
