let allData = []; 
let currentDataType = ''; 
let allPlayers = []; 


function fetchStats() {
const statsContainer = document.getElementById('stats-container');
const dataContainer = document.getElementById('data-container');
const filterContainer = document.getElementById('filter-container');

if (currentDataType === 'stats' && statsContainer.style.display === 'block') {
    statsContainer.style.display = 'none';
    currentDataType = '';
    return;
}

currentDataType = 'stats';
dataContainer.style.display = 'none';
if (filterContainer) filterContainer.style.display = 'none';
statsContainer.style.display = 'block';

fetch('load_data.php?type=records')
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error('Error:', data.error);
            statsContainer.innerHTML = '<p style="color:red;">' + data.error + '</p>';
        } else {
            displayStats(data);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        statsContainer.innerHTML = '<p style="color:red;">Error fetching stats data from server.</p>';
    });
}

function displayStats(data) {
const statsContainer = document.getElementById('stats-container');
statsContainer.innerHTML = '<h2>DETAILED STATISTICS</h2>';

// Store data globally for sorting
window.statsData = data;

// Create Vehicle Statistics section with dropdown
let vehicleStatsHTML = '<div class="stats-section">';
vehicleStatsHTML += '<div class="vehicle-header" style="position: relative; display: flex; align-items: center; justify-content: flex-end; margin-bottom: 15px;">';
vehicleStatsHTML += '<h3 class="vehicle-title" style="position: absolute; left: 50%; transform: translateX(-50%); margin: 0;">Vehicle Statistics</h3>';
vehicleStatsHTML += '<div class="vehicle-select-wrap" style="margin-left: auto;">';
vehicleStatsHTML += '<select id="vehicle-sort-select" onchange="updateVehicleStats()" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc; font-size: 14px;">';
vehicleStatsHTML += '<option value="total-distance">Total Distance</option>';
vehicleStatsHTML += '<option value="longest-distance">Longest Distance</option>';
vehicleStatsHTML += '<option value="avg-placement">Average Placement </option>';
vehicleStatsHTML += '<option value="highest-placement">Highest Placement</option>';
vehicleStatsHTML += '<option value="lowest-placement">Lowest Placement</option>';
vehicleStatsHTML += '</select>';
vehicleStatsHTML += '</div>';
vehicleStatsHTML += '</div>';
vehicleStatsHTML += '<div id="vehicle-stats-table"></div>';
vehicleStatsHTML += '</div>';
statsContainer.innerHTML += vehicleStatsHTML;

// Display initial vehicle stats (Total Distance)
updateVehicleStats();


// Vehicle Rankings by Adventure Stars
const specialMaps = ['Forest Trials', 'Intense City', 'Raging Winter'];
const vehicleStars = {};
data.forEach(record => {
    if (!vehicleStars[record.vehicle_name]) {
        vehicleStars[record.vehicle_name] = 0;
    }
    
    const isSpecialMap = specialMaps.includes(record.map_name);
    let stars = 0;
    
    if (isSpecialMap) {
        // Maps spéciales: >= 5000 = 15000 étoiles, sinon distance * 3
        stars = record.distance >= 5000 ? 15000 : record.distance * 3;
    } else {
        // Maps normales: >= 10000 = 10000 étoiles, sinon distance
        stars = record.distance >= 10000 ? 10000 : record.distance;
    }
    
    vehicleStars[record.vehicle_name] += stars;
});

const sortedVehiclesByStars = Object.entries(vehicleStars)
    .sort((a, b) => b[1] - a[1]);

let starsHTML = '<div class="stats-section"><h3>Vehicle Rankings by Adventure Stars</h3>';
starsHTML += '<div class="chart-container">';

const maxStars = Math.max(...sortedVehiclesByStars.map(v => v[1]));
sortedVehiclesByStars.forEach((vehicle, index) => {
    const barWidth = (vehicle[1] / maxStars) * 100;
    starsHTML += `
        <div class="chart-bar">
            <span class="player-rank">${index + 1}.</span>
            <span class="player-name">${vehicle[0]}</span>
            <div class="bar" style="width: ${barWidth}%; background : linear-gradient(to right, #85a728ff, #28a745);">
                <span class="bar-value">${vehicle[1].toLocaleString()}</span>
            </div>
        </div>
    `;
});

starsHTML += '</div></div>';
statsContainer.innerHTML += starsHTML;

const playerRecords = {};
data.forEach(record => {
    playerRecords[record.player_name] = (playerRecords[record.player_name] || 0) + 1;
});

const sortedPlayers = Object.entries(playerRecords)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10);

let playersHTML = '<div class="stats-section"><h3>Top 10 Players by Record Count</h3>';
playersHTML += '<div class="chart-container">';

const maxRecords = Math.max(...sortedPlayers.map(p => p[1]));
sortedPlayers.forEach((player, index) => {
    const barWidth = (player[1] / maxRecords) * 100;
    playersHTML += `
        <div class="chart-bar">
            <span class="player-rank">${index + 1}.</span>
            <span class="player-name">${player[0]}</span>
            <div class="bar" style="width: ${barWidth}%;">
                <span class="bar-value">${player[1]}</span>
            </div>
        </div>
    `;
});

playersHTML += '</div></div>';
statsContainer.innerHTML += playersHTML;

const countryCounts = {};
data.forEach(record => {
    const c = (record.player_country || record.country || 'Unknown') || 'Unknown';
    countryCounts[c] = (countryCounts[c] || 0) + 1;
});

const grouped = {};
let otherCount = 0;
Object.entries(countryCounts).forEach(([country, count]) => {
    if (count <= 5) {
        otherCount += count;
    } else {
        grouped[country] = count;
    }
});
if (otherCount > 0) grouped['Other countries'] = otherCount;

const countryEntries = Object.entries(grouped).sort((a, b) => b[1] - a[1]);
const countryTotal = countryEntries.reduce((s, e) => s + e[1], 0) || 1;

let pieHTML = '<div class="stats-section"><h3>Records by Country</h3><div class="pie-container">';
pieHTML += '<canvas id="country-pie" width="500" height="375" aria-label="Pie chart showing records by country"></canvas>';
pieHTML += '<div class="pie-legend">';
countryEntries.forEach((entry, idx) => {
    pieHTML += `<div class="legend-item"><span class="legend-color" data-idx="${idx}"></span><span class="legend-label">${entry[0]} (${entry[1]})</span></div>`;
});
pieHTML += '</div></div></div>';
statsContainer.innerHTML += pieHTML;

setTimeout(function drawPieDelay() {
    const canvas = document.getElementById('country-pie');
    if (!canvas || !canvas.getContext) {
        console.warn('Canvas not found or no 2D context');
        return;
    }
    const ctx = canvas.getContext('2d');
    
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    const cx = canvas.width / 2;
    const cy = canvas.height / 2;
    const radius = Math.min(canvas.width, canvas.height) / 2 - 10;

    let startAngle = -0.5 * Math.PI;
    countryEntries.forEach((entry, idx) => {
        const slice = entry[1] / countryTotal * Math.PI * 2;
        const hue = (idx * 137.508) % 360;
        const color = `hsl(${hue},70%,50%)`;
        
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, radius, startAngle, startAngle + slice);
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();

        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.stroke();

        const legendBox = document.querySelector(`.legend-color[data-idx="${idx}"]`);
        if (legendBox) legendBox.style.background = color;

        startAngle += slice;
    });
}, 50);

const mapStats = {};
const mapRecordCount = {};
data.forEach(record => {
    if (!mapStats[record.map_name]) {
        mapStats[record.map_name] = { distance: 0, count: 0 };
    }
    mapStats[record.map_name].distance += record.distance;
    mapStats[record.map_name].count += 1;
});

const sortedMaps = Object.entries(mapStats)
    .sort((a, b) => b[1].distance - a[1].distance);

let mapHTML = '<div class="stats-section"><h3>Map Statistics</h3><table>';
mapHTML += '<tr><th>Map Name</th><th>Total Records</th><th>Total Distance</th><th>Average Distance</th></tr>';
sortedMaps.forEach(map => {
    const avgDistance = (map[1].distance / map[1].count).toFixed(2);
    mapHTML += `<tr><td>${map[0]}</td><td>${map[1].count}</td><td>${map[1].distance}</td><td>${avgDistance}</td></tr>`;
});
mapHTML += '</table></div>';
statsContainer.innerHTML += mapHTML;

const totalRecords = data.length;
const totalDistance = data.reduce((sum, record) => sum + record.distance, 0);
const avgDistance = (totalDistance / totalRecords).toFixed(2);
const uniquePlayers = new Set(data.map(r => r.player_name)).size;
const uniqueVehicles = new Set(data.map(r => r.vehicle_name)).size;
const uniqueMaps = new Set(data.map(r => r.map_name)).size;

let overallHTML = '<div class="stats-section"><h3>Overall Statistics</h3><div class="overall-stats">';
overallHTML += `<div class="stat-box"><strong>Total Records:</strong> ${totalRecords}</div>`;
overallHTML += `<div class="stat-box"><strong>Total Distance:</strong> ${totalDistance}</div>`;
overallHTML += `<div class="stat-box"><strong>Average Distance:</strong> ${avgDistance}</div>`;
overallHTML += `<div class="stat-box"><strong>Unique Players:</strong> ${uniquePlayers}</div>`;
overallHTML += `<div class="stat-box"><strong>Unique Vehicles:</strong> ${uniqueVehicles}</div>`;
overallHTML += `<div class="stat-box"><strong>Unique Maps:</strong> ${uniqueMaps}</div>`;
overallHTML += '</div></div>';
statsContainer.innerHTML += overallHTML;
}

function updateVehicleStats() {
const data = window.statsData;
const sortType = document.getElementById('vehicle-sort-select').value;
const tableContainer = document.getElementById('vehicle-stats-table');
let html = '<table>';

if (sortType === 'total-distance') {
    // Sort by Total Distance
    const vehicleStats = {};
    data.forEach(record => {
        if (!vehicleStats[record.vehicle_name]) {
            vehicleStats[record.vehicle_name] = 0;
        }
        vehicleStats[record.vehicle_name] += record.distance;
    });

    const sortedVehicles = Object.entries(vehicleStats).sort((a, b) => b[1] - a[1]);
    
    html += '<tr><th>Rank</th><th>Vehicle Name</th><th>Total Distance</th></tr>';
    sortedVehicles.forEach((vehicle, index) => {
        html += `<tr><td>${index + 1}</td><td>${vehicle[0]}</td><td>${vehicle[1].toLocaleString()}</td></tr>`;
    });

} else if (sortType === 'longest-distance') {
    // Sort by Longest Distance with Map name
    const vehicleLongest = {};
    data.forEach(record => {
        if (!vehicleLongest[record.vehicle_name]) {
            vehicleLongest[record.vehicle_name] = { distance: 0, map: '' };
        }
        if (record.distance > vehicleLongest[record.vehicle_name].distance) {
            vehicleLongest[record.vehicle_name] = { distance: record.distance, map: record.map_name };
        }
    });

    const sortedByLongest = Object.entries(vehicleLongest)
        .sort((a, b) => b[1].distance - a[1].distance);

    html += '<tr><th>Rank</th><th>Vehicle Name</th><th>Longest Distance</th><th>Map</th></tr>';
    sortedByLongest.forEach((vehicle, index) => {
        html += `<tr><td>${index + 1}</td><td>${vehicle[0]}</td><td>${vehicle[1].distance.toLocaleString()}</td><td>${vehicle[1].map}</td></tr>`;
    });

} else if (sortType === 'avg-placement') {
    // Sort by Average Placement (ascending)
    const vehiclePlacements = {};
    
    // Group records by map
    const mapData = {};
    data.forEach(record => {
        if (!mapData[record.map_name]) {
            mapData[record.map_name] = [];
        }
        mapData[record.map_name].push(record);
    });

    // Calculate placement for each vehicle on each map
    Object.keys(mapData).forEach(mapName => {
        const mapRecords = mapData[mapName];
        // Sort by distance descending to get placements
        mapRecords.sort((a, b) => b.distance - a.distance);
        
        mapRecords.forEach((record, placement) => {
            if (!vehiclePlacements[record.vehicle_name]) {
                vehiclePlacements[record.vehicle_name] = { placements: [], totalPlacement: 0 };
            }
            vehiclePlacements[record.vehicle_name].placements.push({ map: mapName, placement: placement + 1, distance: record.distance });
            vehiclePlacements[record.vehicle_name].totalPlacement += (placement + 1);
        });
    });

    // Calculate average placement
    Object.keys(vehiclePlacements).forEach(vehicleName => {
        const data = vehiclePlacements[vehicleName];
        data.avgPlacement = data.totalPlacement / data.placements.length;
    });

    const sortedByAvgPlacement = Object.entries(vehiclePlacements)
        .sort((a, b) => a[1].avgPlacement - b[1].avgPlacement);

    html += '<tr><th>Rank</th><th>Vehicle Name</th><th>Average Placement</th></tr>';
    sortedByAvgPlacement.forEach((vehicle, index) => {
        html += `<tr><td>${index + 1}</td><td>${vehicle[0]}</td><td>${vehicle[1].avgPlacement.toFixed(2)}</td></tr>`;
    });

} else if (sortType === 'highest-placement') {
    // Sort by Highest Placement (1st place across all maps)
    const vehicleHighest = {};
    
    const mapData = {};
    data.forEach(record => {
        if (!mapData[record.map_name]) {
            mapData[record.map_name] = [];
        }
        mapData[record.map_name].push(record);
    });

    // Find highest placement for each vehicle
    Object.keys(mapData).forEach(mapName => {
        const mapRecords = mapData[mapName];
        mapRecords.sort((a, b) => b.distance - a.distance);
        
        mapRecords.forEach((record, placement) => {
            if (!vehicleHighest[record.vehicle_name]) {
                vehicleHighest[record.vehicle_name] = { placement: Infinity, maps: [] };
            }
            const currentPlacement = placement + 1;
            if (currentPlacement < vehicleHighest[record.vehicle_name].placement) {
                vehicleHighest[record.vehicle_name].placement = currentPlacement;
                vehicleHighest[record.vehicle_name].maps = [mapName];
            } else if (currentPlacement === vehicleHighest[record.vehicle_name].placement) {
                vehicleHighest[record.vehicle_name].maps.push(mapName);
            }
        });
    });

    const sortedByHighest = Object.entries(vehicleHighest)
        .filter(v => v[1].placement !== Infinity)
        .sort((a, b) => a[1].placement - b[1].placement);

    html += '<tr><th>Rank</th><th>Vehicle Name</th><th>Best Placement</th><th>Maps</th></tr>';
    sortedByHighest.forEach((vehicle, index) => {
        const mapsStr = vehicle[1].maps.join(', ');
        html += `<tr><td>${index + 1}</td><td>${vehicle[0]}</td><td>#${vehicle[1].placement}</td><td>${mapsStr}</td></tr>`;
    });

} else if (sortType === 'lowest-placement') {
    // Sort by Lowest Placement (worst place across all maps)
    const vehicleLowest = {};
    
    const mapData = {};
    data.forEach(record => {
        if (!mapData[record.map_name]) {
            mapData[record.map_name] = [];
        }
        mapData[record.map_name].push(record);
    });

    // Find lowest placement for each vehicle
    Object.keys(mapData).forEach(mapName => {
        const mapRecords = mapData[mapName];
        mapRecords.sort((a, b) => b.distance - a.distance);
        
        mapRecords.forEach((record, placement) => {
            if (!vehicleLowest[record.vehicle_name]) {
                vehicleLowest[record.vehicle_name] = { placement: 0, maps: [] };
            }
            const currentPlacement = placement + 1;
            if (currentPlacement > vehicleLowest[record.vehicle_name].placement) {
                vehicleLowest[record.vehicle_name].placement = currentPlacement;
                vehicleLowest[record.vehicle_name].maps = [mapName];
            } else if (currentPlacement === vehicleLowest[record.vehicle_name].placement) {
                vehicleLowest[record.vehicle_name].maps.push(mapName);
            }
        });
    });

    const sortedByLowest = Object.entries(vehicleLowest)
        .sort((a, b) => b[1].placement - a[1].placement);

    html += '<tr><th>Rank</th><th>Vehicle Name</th><th>Worst Placement</th><th>Maps</th></tr>';
    sortedByLowest.forEach((vehicle, index) => {
        const mapsStr = vehicle[1].maps.join(', ');
        html += `<tr><td>${index + 1}</td><td>${vehicle[0]}</td><td>#${vehicle[1].placement}</td><td>${mapsStr}</td></tr>`;
    });
}

html += '</table>';
tableContainer.innerHTML = html;
}

function filterPlayers() {
const query = document.getElementById('player-filter').value.toLowerCase();
const select = document.getElementById('player-select');
select.innerHTML = '<option value="">Select an Existing Player</option>';
allPlayers
    .filter(p => p.namePlayer.toLowerCase().includes(query))
    .forEach(p => {
        const option = document.createElement('option');
        option.value = p.idPlayer;
        option.textContent = p.namePlayer;
        select.appendChild(option);
    });
handlePlayerSelection();
}

function handlePlayerSelection() {
const select = document.getElementById('player-select');
const countryInput = document.getElementById('country-input');
const playerId = select.value;
const newPlayerInput = document.getElementById('new-player-input');

if (playerId) {
    const player = allPlayers.find(p => String(p.idPlayer) === String(playerId));
    if (player) {
        countryInput.value = player.country || '';
        countryInput.disabled = true;
    } else {
        countryInput.value = '';
        countryInput.disabled = false;
    }
    newPlayerInput.value = '';
} else {
    countryInput.value = '';
    countryInput.disabled = false;
}
}

function newPlayerTyped() {
const newPlayerInput = document.getElementById('new-player-input');
const playerSelect = document.getElementById('player-select');
const countryInput = document.getElementById('country-input');

if (newPlayerInput.value && newPlayerInput.value.trim() !== '') {
    playerSelect.value = '';
    countryInput.disabled = false;
}
}

function fetchData(dataType) {
const container = document.getElementById('data-container');
const filterContainer = document.getElementById('filter-container');
const statsContainer = document.getElementById('stats-container');
statsContainer.style.display = 'none';

if (currentDataType === dataType && container.style.display === 'block') {
    container.style.display = 'none';
    if (filterContainer) filterContainer.style.display = 'none'; 
    currentDataType = ''; 
    return;
}

currentDataType = dataType; 
container.style.display = 'block'; 

if (dataType === 'records') {
    if (filterContainer) filterContainer.style.display = 'flex'; 
} else {
    if (filterContainer) filterContainer.style.display = 'none'; 
}

fetch('load_data.php?type=' + dataType)
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error('Error:', data.error);
            container.innerHTML = '<p style="color:red;">' + data.error + '</p>';
        } else {
            allData = data; 
            displayData(data, dataType);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        container.innerHTML = '<p style="color:red;">Error fetching data from server.</p>';
    });
}

function fetchSummary() {
fetch('load_data.php?type=records')
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error('Error:', data.error);
            document.getElementById('summary-container').innerHTML = '<p style="color:red;">' + data.error + '</p>';
        } else {
            displaySummary(data);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        document.getElementById('summary-container').innerHTML = '<p style="color:red;">Error fetching summary data from server.</p>';
    });
}

function displaySummary(data) {
const summaryContainer = document.getElementById('summary-container');
const playerRecords = {};
const vehicleDistances = {};

data.forEach(record => {
    
    playerRecords[record.player_name] = (playerRecords[record.player_name] || 0) + 1;

    
    vehicleDistances[record.vehicle_name] = (vehicleDistances[record.vehicle_name] || 0) + record.distance;
});


const bestPlayer = Object.keys(playerRecords).reduce((a, b) => playerRecords[a] > playerRecords[b] ? a : b);
const bestVehicle = Object.keys(vehicleDistances).reduce((a, b) => vehicleDistances[a] > vehicleDistances[b] ? a : b);

summaryContainer.innerHTML = `
    <h2>Summary 📝</h2>
    <div class="summary-box">
        <p><strong>Best Player:</strong> ${bestPlayer} (${playerRecords[bestPlayer]} records)</p>
        <p><strong>Best Vehicle:</strong> ${bestVehicle} (${vehicleDistances[bestVehicle]} total distance)</p>
    </div>
`;
}


function displayData(data, dataType) {
const container = document.getElementById('data-container');
container.innerHTML = ''; 


if (dataType === 'records' && !document.getElementById('filter-container')) {
    addSearchAndFilter();
}

container.innerHTML += '<h2>' + dataType.toUpperCase() + '</h2>'; 

if (data.length === 0) {
    container.innerHTML += '<p>No data available.</p>';
    return;
}

let tableHTML = '<table>';
if (dataType === 'maps') {
    tableHTML += '<tr><th>Map ID</th><th>Map Name</th></tr>';
    data.forEach(item => {
        tableHTML += `<tr><td>${item.idMap}</td><td>${item.nameMap}</td></tr>`;
    });
} else if (dataType === 'vehicles') {
    tableHTML += '<tr><th>Vehicle ID</th><th>Vehicle Name</th></tr>';
    data.forEach(item => {
        tableHTML += `<tr><td>${item.idVehicle}</td><td>${item.nameVehicle}</td></tr>`;
    });
} else if (dataType === 'players') {
    tableHTML += '<tr><th>Player ID</th><th>Player Name</th><th>Country</th></tr>';
    data.forEach(item => {
        tableHTML += `<tr><td>${item.idPlayer}</td><td>${item.namePlayer}</td><td>${item.country}</td></tr>`;
    });
} else if (dataType === 'records') {
    const sortSelect = document.getElementById('sort-select');
    const sortVal = sortSelect ? sortSelect.value : 'default';

    const records = Array.isArray(data) ? [...data] : [];

    const getMapId = r => {
        const v = r.idMap ?? r.mapId ?? r.map_id ?? null;
        return (v !== null && !isNaN(Number(v))) ? Number(v) : null;
    };
    const getVehicleId = r => {
        const v = r.idVehicle ?? r.vehicleId ?? r.vehicle_id ?? null;
        return (v !== null && !isNaN(Number(v))) ? Number(v) : null;
    };

    if (sortVal === 'dist-asc') {
        records.sort((a, b) => Number(a.distance) - Number(b.distance));
    } else if (sortVal === 'dist-desc') {
        records.sort((a, b) => Number(b.distance) - Number(a.distance));
    } else {
        records.sort((a, b) => {
            const ai = getMapId(a), bi = getMapId(b);
            if (ai !== null && bi !== null && ai !== bi) return ai - bi;

            const mapComp = String(a.map_name || a.nameMap || '').localeCompare(String(b.map_name || b.nameMap || ''));
            if (mapComp !== 0) return mapComp;

            const av = getVehicleId(a), bv = getVehicleId(b);
            if (av !== null && bv !== null && av !== bv) return av - bv;

            return String(a.vehicle_name || a.nameVehicle || '').localeCompare(String(b.vehicle_name || b.nameVehicle || ''));
        });
    }

    tableHTML += '<tr><th>Distance</th><th>Map Name</th><th>Vehicle Name</th><th>Player Name</th><th>Player Country</th></tr>';
    records.forEach(item => {
        tableHTML += `<tr>
                        <td>${item.distance}</td>
                        <td>${item.map_name}</td>
                        <td>${item.vehicle_name}</td>
                        <td>${item.player_name}</td>
                        <td>${item.player_country}</td>
                        </tr>`;
    });
}
tableHTML += '</table>';
container.innerHTML += tableHTML;
}


function addSearchAndFilter() {
const container = document.getElementById('data-container');
const searchHTML = `
    <div id="filter-container" class="filter-container">
        <input type="text" id="search-bar" placeholder="Search by player, map, or vehicle..." oninput="filterRecords()">
        <select id="map-filter" onchange="filterRecords()">
            <option value="">Filter by Map</option>
            ${[...new Set(allData.map(record => record.map_name))].map(map => `<option value="${map}">${map}</option>`).join('')}
        </select>
        <select id="sort-select" onchange="filterRecords()" title="Sort records">
            <option value="default">Default: Map / Vehicle Alphabetically</option>
            <option value="dist-asc">Distance ↑ (ascending)</option>
            <option value="dist-desc">Distance ↓ (descending)</option>
        </select>
        <select id="vehicle-filter" onchange="filterRecords()">
            <option value="">Filter by Vehicle</option>
            ${[...new Set(allData.map(record => record.vehicle_name))].map(vehicle => `<option value="${vehicle}">${vehicle}</option>`).join('')}
        </select>
    </div>
`;
container.insertAdjacentHTML('beforebegin', searchHTML);
}


function filterRecords() {
const searchQuery = document.getElementById('search-bar').value.toLowerCase();
const mapFilter = document.getElementById('map-filter').value;
const vehicleFilter = document.getElementById('vehicle-filter').value;

const filteredData = allData.filter(record => {
    const matchesSearch = record.player_name.toLowerCase().includes(searchQuery) ||
                            record.map_name.toLowerCase().includes(searchQuery) ||
                            record.vehicle_name.toLowerCase().includes(searchQuery);
    const matchesMap = !mapFilter || record.map_name === mapFilter;
    const matchesVehicle = !vehicleFilter || record.vehicle_name === vehicleFilter;

    return matchesSearch && matchesMap && matchesVehicle;
});

displayData(filteredData, currentDataType);
}


async function checkAuthAndInit() {
try {
    const res = await fetch('auth/status.php', { cache: 'no-cache', credentials: 'same-origin' });
    const status = await res.json();
    const loginBtn = document.getElementById('login-btn');
    const logoutBtn = document.getElementById('logout-btn');
    const adminBtn = document.getElementById('admin-btn');
    const authWarning = document.getElementById('auth-warning');

    if (status.logged) {
        if (loginBtn) loginBtn.style.display = 'none';
        if (logoutBtn) { logoutBtn.style.display = 'inline-block'; logoutBtn.textContent = 'Logout (' + (status.username || status.id) + ')'; }
    } else {
        if (loginBtn) loginBtn.style.display = 'inline-block';
        if (logoutBtn) logoutBtn.style.display = 'none';
    }

    if (status.logged && status.allowed) {
        if (adminBtn) {
            adminBtn.style.display = 'inline-block';
            adminBtn.onclick = () => location.href = '/admin.php';
        }
        if (authWarning) authWarning.textContent = '';
    } else {
        if (adminBtn) adminBtn.style.display = 'none';
        if (status.logged && !status.allowed) {
            if (authWarning) authWarning.textContent = 'Logged in as ' + (status.username || status.id) + ' — you do not have permission to edit records.';
        } else {
            if (authWarning) authWarning.textContent = '';
        }
    }
} catch (err) {
    console.error('Auth check failed', err);
}
}

window.onload = () => {
fetchSummary();
checkAuthAndInit();
};

function submitRecord(event) {
event.preventDefault();

const mapId = document.getElementById('map-select').value;
const vehicleId = document.getElementById('vehicle-select').value;
const distance = document.getElementById('distance-input').value;
const playerId = document.getElementById('player-select').value;
const newPlayerName = document.getElementById('new-player-input').value;
const country = document.getElementById('country-input').value;

if (!playerId && !newPlayerName) {
    document.getElementById('form-message').textContent = 'Please select an existing player or add a new one.';
    document.getElementById('form-message').style.color = 'red';
    return;
}

if (!playerId && newPlayerName && !country) {
    document.getElementById('form-message').textContent = 'Please provide a country for the new player.';
    document.getElementById('form-message').style.color = 'red';
    return;
}

const selectedPlayerOption = document.getElementById('player-select').selectedOptions[0];
const selectedPlayerName = selectedPlayerOption ? selectedPlayerOption.textContent : '';

const hasPlayerId = (playerId !== null && playerId !== undefined && playerId !== '');
const formData = hasPlayerId ? {
    mapId,
    vehicleId,
    distance,
    playerId,
    playerName: selectedPlayerName
} : {
    mapId,
    vehicleId,
    distance,
    playerId: null,
    newPlayerName,
    country
};

fetch('submit_record.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(formData)
})
.then(async response => {
    const data = await response.json().catch(()=>({ error: 'Invalid server response' }));
    if (!response.ok) {
        const msg = data.error || (response.status === 401 ? 'Authentication required.' : response.status === 403 ? 'Forbidden: insufficient permissions.' : 'Server error');
        document.getElementById('form-message').textContent = 'Error: ' + msg;
        document.getElementById('form-message').style.color = 'red';
        return;
    }
    if (data.success) {
        const mapName = data.mapName || 'Unknown';
        const vehicleName = data.vehicleName || 'Unknown';
        const playerName = data.playerName || 'Unknown';
        const distance = data.distance || '?';
        
        const successMsg = `✅ Record submitted! | ${playerName} | ${mapName} | ${vehicleName} | ${distance}m`;
        const msgEl = document.getElementById('form-message');
        msgEl.textContent = successMsg;
        msgEl.style.color = 'green';
        msgEl.style.display = 'block';
        document.getElementById('record-form').reset();

        populateDeleteOptions();
        populateFormOptions();
        
        // Garder le message visible pendant 5 secondes
        setTimeout(() => {
            msgEl.textContent = '';
        }, 5000);
    } else {
        document.getElementById('form-message').textContent = 'Error: ' + (data.error || 'Unknown error');
        document.getElementById('form-message').style.color = 'red';
    }
})
.catch(error => {
    document.getElementById('form-message').textContent = 'Error submitting record.';
    document.getElementById('form-message').style.color = 'red';
});
}

function populateDeleteOptions() {
fetch('load_data.php?type=records')
    .then(response => response.json())
    .then(data => {
        const recordSelect = document.getElementById('record-select');
        recordSelect.innerHTML = '<option value="">Select a Record</option>';
        (data || []).forEach(record => {
            const option = document.createElement('option');
            option.value = record.idRecord;
            option.textContent = `${record.distance} - ${record.map_name} - ${record.vehicle_name} - ${record.player_name}`;
            recordSelect.appendChild(option);
        });
    });
}

function deleteRecord(event) {
event.preventDefault();

const recordId = document.getElementById('record-select').value;

if (!recordId) {
    document.getElementById('delete-message').textContent = 'Please select a record to delete.';
    document.getElementById('delete-message').style.color = 'red';
    return;
}

fetch('delete_record.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({ recordId })
})
.then(async response => {
    const data = await response.json().catch(()=>({ error: 'Invalid server response' }));
    if (!response.ok) {
        const msg = data.error || (response.status === 401 ? 'Authentication required.' : response.status === 403 ? 'Forbidden: insufficient permissions.' : 'Server error');
        document.getElementById('delete-message').textContent = 'Error: ' + msg;
        document.getElementById('delete-message').style.color = 'red';
        return;
    }
    if (data.success) {
        document.getElementById('delete-message').textContent = 'Record deleted successfully!';
        document.getElementById('delete-message').style.color = 'green';
        populateDeleteOptions();
    } else {
        document.getElementById('delete-message').textContent = 'Error: ' + (data.error || 'Unknown error');
        document.getElementById('delete-message').style.color = 'red';
    }
})
.catch(error => {
    document.getElementById('delete-message').textContent = 'Error deleting record.';
    document.getElementById('delete-message').style.color = 'red';
});
}