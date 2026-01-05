let allData = []; 
let currentDataType = ''; 
let allPlayers = []; 

function esc(input) {
    if (input === null || input === undefined) return '';
    return String(input)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function fetchWithTimeout(resource, options = {}, timeout = 3000) {
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);
    const opts = Object.assign({}, options, { signal: controller.signal });
    return fetch(resource, opts).finally(() => clearTimeout(id));
}


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

    fetch('php/load_data.php?type=records&t=' + Date.now())
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

window.statsData = data;

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

updateVehicleStats();

const specialMaps = ['Forest Trials', 'Intense City', 'Raging Winter'];
const vehicleStars = {};
data.forEach(record => {
    if (!vehicleStars[record.vehicle_name]) {
        vehicleStars[record.vehicle_name] = 0;
    }
    
    const isSpecialMap = specialMaps.includes(record.map_name);
    let stars = 0;
    
    if (isSpecialMap) {
        stars = record.distance >= 5000 ? 15000 : record.distance * 3;
    } else {
        stars = record.distance >= 10000 ? 10000 : record.distance;
    }
    
    vehicleStars[record.vehicle_name] += stars;
});

const sortedVehiclesByStars = Object.entries(vehicleStars)
    .sort((a, b) => b[1] - a[1]);

let totalStars = 0;
sortedVehiclesByStars.forEach(v => { totalStars += v[1]; });

let starsHTML = '<div class="stats-section"><h3>Vehicle Rankings by Adventure Stars</h3>';
// add stars-chart so green bars use the flexible bar structure
starsHTML += '<div class="chart-container stars-chart">';

const maxStars = Math.max(...sortedVehiclesByStars.map(v => v[1]));
sortedVehiclesByStars.forEach((vehicle, index) => {
    const barWidth = (vehicle[1] / maxStars) * 100;
    starsHTML += `
        <div class="chart-bar">
            <span class="player-rank">${index + 1}.</span>
            <span class="player-name">${esc(vehicle[0])}</span>
            <div class="bar-wrap">
                <div class="bar-fill" style="width: ${barWidth}%; background: linear-gradient(to right, #85a728ff, #28a745);">
                    <span class="bar-value">${vehicle[1].toLocaleString()}</span>
                </div>
            </div>
        </div>
    `;
});

starsHTML += `<div class="total-stars"> ⭐ Total Adventure Stars : </div>`;
starsHTML += `<div class="total-stars-value">${totalStars.toLocaleString()}</div>`;
starsHTML += '</div></div>';
statsContainer.innerHTML += starsHTML;

// explicitly set star bar fills to ensure accurate rendering on mobile browsers
try {
    const starFills = statsContainer.querySelectorAll('.stars-chart .bar-fill');
    (starFills || []).forEach((el, i) => {
        const pct = (sortedVehiclesByStars[i] && sortedVehiclesByStars[i][1]) ? (sortedVehiclesByStars[i][1] / maxStars) * 100 : 0;
        el.style.width = pct.toFixed(2) + '%';
    });
} catch (e) {}

const playerRecords = {};
data.forEach(record => {
    playerRecords[record.player_name] = (playerRecords[record.player_name] || 0) + 1;
});

const sortedPlayers = Object.entries(playerRecords)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10);

let playersHTML = '<div class="stats-section"><h3>Top 10 Players by Record Count</h3>';
// add a modifier class so player bars can be styled separately
playersHTML += '<div class="chart-container players-chart">';

const maxRecords = Math.max(...sortedPlayers.map(p => p[1]));
sortedPlayers.forEach((player, index) => {
    const barWidth = (player[1] / maxRecords) * 100;
    playersHTML += `
        <div class="chart-bar">
            <span class="player-rank">${index + 1}.</span>
            <span class="player-name">${esc(player[0])}</span>
            <div class="bar-wrap">
                <div class="bar-fill" style="width: ${barWidth}%;">
                    <span class="bar-value">${player[1]}</span>
                </div>
            </div>
        </div>
    `;
});

playersHTML += '</div></div>';
statsContainer.innerHTML += playersHTML;

// ensure fill widths are explicitly set (helps avoid any rendering inconsistencies on some mobile browsers)
try {
    const fills = statsContainer.querySelectorAll('.players-chart .bar-fill');
    const max = Math.max(...sortedPlayers.map(p => p[1])) || 1;
    fills.forEach((el, i) => {
        const val = (sortedPlayers[i] && sortedPlayers[i][1]) ? sortedPlayers[i][1] : 0;
        const pct = (val / max) * 100;
        el.style.width = pct.toFixed(2) + '%';
    });
} catch (e) {
    // non-fatal
}

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
    pieHTML += `<div class="legend-item"><span class="legend-color" data-idx="${idx}"></span><span class="legend-label">${esc(entry[0])} (${entry[1]})</span></div>`;
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
    mapHTML += `<tr><td>${esc(map[0])}</td><td>${map[1].count}</td><td>${map[1].distance}</td><td>${avgDistance}</td></tr>`;
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
overallHTML += `<div class="stat-box"><strong>Total Records:</strong> ${esc(totalRecords)}</div>`;
overallHTML += `<div class="stat-box"><strong>Total Distance:</strong> ${esc(totalDistance)}</div>`;
overallHTML += `<div class="stat-box"><strong>Average Distance:</strong> ${esc(avgDistance)}</div>`;
overallHTML += `<div class="stat-box"><strong>Unique Players:</strong> ${esc(uniquePlayers)}</div>`;
overallHTML += `<div class="stat-box"><strong>Unique Vehicles:</strong> ${esc(uniqueVehicles)}</div>`;
overallHTML += `<div class="stat-box"><strong>Unique Maps:</strong> ${esc(uniqueMaps)}</div>`;
overallHTML += '</div></div>';
statsContainer.innerHTML += overallHTML;
}

function updateVehicleStats() {
const data = window.statsData;
const sortType = document.getElementById('vehicle-sort-select').value;
const tableContainer = document.getElementById('vehicle-stats-table');
let html = '<table>';

if (sortType === 'total-distance') {
    
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
    
    const vehiclePlacements = {};
    
    
    const mapData = {};
    data.forEach(record => {
        if (!mapData[record.map_name]) {
            mapData[record.map_name] = [];
        }
        mapData[record.map_name].push(record);
    });

    
    Object.keys(mapData).forEach(mapName => {
        const mapRecords = mapData[mapName];
        
        mapRecords.sort((a, b) => b.distance - a.distance);
        
        mapRecords.forEach((record, placement) => {
            if (!vehiclePlacements[record.vehicle_name]) {
                vehiclePlacements[record.vehicle_name] = { placements: [], totalPlacement: 0 };
            }
            vehiclePlacements[record.vehicle_name].placements.push({ map: mapName, placement: placement + 1, distance: record.distance });
            vehiclePlacements[record.vehicle_name].totalPlacement += (placement + 1);
        });
    });

    
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
    
    const vehicleHighest = {};
    
    const mapData = {};
    data.forEach(record => {
        if (!mapData[record.map_name]) {
            mapData[record.map_name] = [];
        }
        mapData[record.map_name].push(record);
    });

    
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
    
    const vehicleLowest = {};
    
    const mapData = {};
    data.forEach(record => {
        if (!mapData[record.map_name]) {
            mapData[record.map_name] = [];
        }
        mapData[record.map_name].push(record);
    });

    
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

function downloadCSV(dataArray) {
    const headers = ['Distance','Map Name','Vehicle Name','Player Name','Country'];
    const rows = dataArray.map(r => [
        r.distance,
        r.map_name,
        r.vehicle_name,
        r.player_name,
        r.player_country
    ]);

    const escapeCell = (v) => {
        if (v === null || v === undefined) return '';
        const s = v.toString();
        if (s.includes(',') || s.includes('"') || s.includes('\n')) {
            return '"' + s.replace(/"/g, '""') + '"';
        }
        return s;
    };

    const csvContent = [headers.map(escapeCell).join(',')].concat(rows.map(r => r.map(escapeCell).join(','))).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const timestamp = new Date().toISOString().split('T')[0];
    const filename = `HCR2_Records_${timestamp}.csv`;

    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, filename);
    } else {
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
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

    fetch('php/load_data.php?type=' + dataType + '&t=' + Date.now())
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
fetch('php/load_data.php?type=records&t=' + Date.now())
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error('Error:', data.error);
            const sc = document.getElementById('summary-container');
            if (sc) sc.innerHTML = '<p style="color:red;">' + esc(data.error) + '</p>';
        } else {
            displaySummary(data);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        const sc = document.getElementById('summary-container');
        if (sc) sc.innerHTML = '<p style="color:red;">Error fetching summary data from server.</p>';
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
        <p><strong>Best Player:</strong> ${esc(bestPlayer)} (${playerRecords[bestPlayer]} records)</p>
        <p><strong>Best Vehicle:</strong> ${esc(bestVehicle)} (${vehicleDistances[bestVehicle]} total distance)</p>
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
                        <td>${esc(item.distance)}</td>
                        <td>${esc(item.map_name)}</td>
                        <td>${esc(item.vehicle_name)}</td>
                        <td>${esc(item.player_name)}</td>
                        <td>${esc(item.player_country)}</td>
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
    <button id="export-btn" onclick="exportToCSV()" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc; background-color: #28a745; color: white; cursor: pointer; font-size: 14px; margin-left: 8px;">📥 Export CSV</button>
        <select id="map-filter" onchange="filterRecords()">
            <option value="">Filter by Map</option>
            ${[...new Set(allData.map(record => record.map_name))].map(map => `<option value="${esc(map)}">${esc(map)}</option>`).join('')}
        </select>
        <select id="sort-select" onchange="filterRecords()" title="Sort records">
            <option value="default">Default: Map / Vehicle Alphabetically</option>
            <option value="dist-asc">Distance ↑ (ascending)</option>
            <option value="dist-desc">Distance ↓ (descending)</option>
        </select>
        <select id="vehicle-filter" onchange="filterRecords()">
            <option value="">Filter by Vehicle</option>
            ${[...new Set(allData.map(record => record.vehicle_name))].map(vehicle => `<option value="${esc(vehicle)}">${esc(vehicle)}</option>`).join('')}
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
    const res = await fetchWithTimeout('auth/status.php', { cache: 'no-cache', credentials: 'same-origin' }, 2500);
    const status = res ? await res.json().catch(()=>({ logged: false })) : { logged: false };
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
            adminBtn.onclick = () => location.href = 'php/admin.php';
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
    checkAuthAndInit();
};

 
async function initGithubVersion() {
    const repo = 'Nipatsuplayer/hcr2-database-with-website';
    const versionEl = document.getElementById('github-version');
    const linkEl = document.getElementById('github-link');
    if (!versionEl || !linkEl) return;

    try {
        let res = await fetchWithTimeout(`https://api.github.com/repos/${repo}/releases/latest`, { }, 3000).catch(()=>null);
        if (res && res.ok) {
            const data = await res.json().catch(()=>null);
            if (data && data.tag_name) {
                versionEl.textContent = esc(data.tag_name);
                linkEl.href = data.html_url || `https://github.com/${repo}/releases`;
                linkEl.title = 'View release on GitHub';
                return;
            }
        }

        res = await fetchWithTimeout(`https://api.github.com/repos/${repo}/commits`, {}, 3000).catch(()=>null);
        if (res && res.ok) {
            const commits = await res.json().catch(()=>null);
            if (Array.isArray(commits) && commits.length > 0) {
                const sha = commits[0].sha ? commits[0].sha.substring(0, 7) : commits[0].sha;
                versionEl.textContent = esc(sha || 'unknown');
                linkEl.href = commits[0].html_url || `https://github.com/${repo}`;
                linkEl.title = 'View commit on GitHub';
                return;
            }
        }
    } catch (err) {
        console.error('Failed to fetch GitHub version:', err);
    }

    versionEl.textContent = 'unknown';
    linkEl.href = `https://github.com/${repo}`;
}

window.addEventListener('load', () => {
    try { initGithubVersion(); } catch (e) { console.error(e); }
});

window.addEventListener('error', function (evt) {
    console.error('Global error:', evt.message, evt.filename + ':' + evt.lineno);
});
window.addEventListener('unhandledrejection', function (evt) {
    console.error('Unhandled promise rejection:', evt.reason);
});

 
function publicSubmitOverlayClick(e) {
    const overlay = document.getElementById('public-submit-overlay');
    const panel = document.querySelector('.modal-panel');
    if (!overlay || !panel) return;
    if (e.target === overlay) {
        togglePublicSubmitForm();
    }
}

function publicSubmitKeyHandler(e) {
    if (e.key === 'Escape') togglePublicSubmitForm();
}

function togglePublicSubmitForm() {
    const overlay = document.getElementById('public-submit-overlay');
    if (!overlay) return;
    const isOpen = overlay.style.display === 'block';
    if (isOpen) {
        overlay.style.display = 'none';
        document.removeEventListener('keydown', publicSubmitKeyHandler);
        overlay.removeEventListener('click', publicSubmitOverlayClick);
    } else {
        overlay.style.display = 'block';
        populatePublicSubmitOptions();
        document.addEventListener('keydown', publicSubmitKeyHandler);
        overlay.addEventListener('click', publicSubmitOverlayClick);
        
        setTimeout(() => {
            const first = document.getElementById('public-map-select');
            if (first) first.focus();
        }, 50);
    }
}

function toggleNewsModal() {
    const overlay = document.getElementById('news-overlay');
    if (!overlay) return;
    const isOpen = overlay.style.display === 'block';
    if (isOpen) {
        overlay.style.display = 'none';
    } else {
        overlay.style.display = 'block';
        loadNews();
    }
}

async function loadNews() {
    const el = document.getElementById('news-list');
    if (!el) return;
    el.innerHTML = '<p>Loading news...</p>';
    try {
        const res = await fetch('php/get_news.php', { cache: 'no-cache' });
        const data = await res.json();
        if (data.error) {
            el.innerHTML = '<p style="color:red;">' + esc(data.error) + '</p>';
            return;
        }
        const items = Array.isArray(data.news) ? data.news : [];
        if (items.length === 0) {
            el.innerHTML = '<p>No news available.</p>';
            return;
        }
        const html = items.map(n => {
            const created = n.created_at ? n.created_at : '';
            const title = esc(n.title || '');
            const content = esc(n.content || '');
            const author = esc(n.author || '');
            return `<div class="news-item" style="padding:12px; border-bottom:1px solid #eee;"><h3 style=\"margin:0 0 6px 0;\">${title}</h3><div style=\"font-size:13px;color:#666;margin-bottom:8px;\">${created} — ${author}</div><div style=\"white-space:pre-wrap;\">${content}</div></div>`;
        }).join('');
        el.innerHTML = html;
    } catch (err) {
        console.error('Failed to load news', err);
        el.innerHTML = '<p style="color:red;">Failed to load news.</p>';
    }
}

async function populatePublicSubmitOptions() {
    try {
        const mapsRes = await fetch('php/load_data.php?type=maps&t=' + Date.now());
        const maps = await mapsRes.json();
        const mapSel = document.getElementById('public-map-select');
        if (mapSel && Array.isArray(maps)) {
            mapSel.innerHTML = '<option value="">Select a Map</option>' + maps.map(m => `<option value="${esc(m.idMap)}">${esc(m.nameMap)}</option>`).join('');
        }

        const vehiclesRes = await fetch('php/load_data.php?type=vehicles&t=' + Date.now());
        const vehicles = await vehiclesRes.json();
        const vehicleSel = document.getElementById('public-vehicle-select');
        if (vehicleSel && Array.isArray(vehicles)) {
            vehicleSel.innerHTML = '<option value="">Select a Vehicle</option>' + vehicles.map(v => `<option value="${esc(v.idVehicle)}">${esc(v.nameVehicle)}</option>`).join('');
        }
    } catch (err) {
        console.error('Failed to load maps/vehicles for public submit', err);
    }
}

async function submitPublicRecord(e) {
    e.preventDefault();
    const mapId = document.getElementById('public-map-select').value;
    const vehicleId = document.getElementById('public-vehicle-select').value;
    const distance = document.getElementById('public-distance-input').value;
    const playerName = document.getElementById('public-player-name').value.trim();
    const playerCountry = document.getElementById('public-player-country').value.trim();
    const msgEl = document.getElementById('public-submit-message');

    if (!mapId || !vehicleId || !distance || !playerName) {
        if (msgEl) { msgEl.textContent = 'Please complete all required fields.'; msgEl.style.color = 'red'; }
        return;
    }
    if (isNaN(Number(distance)) || Number(distance) <= 0) {
        if (msgEl) { msgEl.textContent = 'Distance must be a positive number.'; msgEl.style.color = 'red'; }
        return;
    }

    try {
        const hp = document.getElementById('hp_email') ? document.getElementById('hp_email').value.trim() : '';
        const res = await fetch('php/public_submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mapId, vehicleId, distance: Number(distance), playerName, playerCountry, hp_email: hp })
        });
        const data = await res.json();
        if (!res.ok) {
            if (msgEl) { msgEl.textContent = data.error || 'Submission failed'; msgEl.style.color = 'red'; }
            return;
        }
        if (data.success) {
            if (msgEl) { msgEl.textContent = data.message || 'Submitted'; msgEl.style.color = 'green'; }
            document.getElementById('public-submit-form').reset();
        } else {
            if (msgEl) { msgEl.textContent = data.error || 'Submission failed'; msgEl.style.color = 'red'; }
        }
    } catch (err) {
        console.error('Public submit failed', err);
        if (msgEl) { msgEl.textContent = 'Submission failed (network error).'; msgEl.style.color = 'red'; }
    }
}

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

fetch('php/submit_record.php', {
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
fetch('php/load_data.php?type=records&t=' + Date.now())
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

fetch('php/delete_record.php', {
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

function exportToCSV() {
    
    const searchQuery = (document.getElementById('search-bar')?.value || '').toLowerCase();
    const mapFilter = (document.getElementById('map-filter')?.value) || '';
    const vehicleFilter = (document.getElementById('vehicle-filter')?.value) || '';

    const filteredData = (allData || []).filter(record => {
        const matchesSearch = (record.player_name || '').toString().toLowerCase().includes(searchQuery) ||
                              (record.map_name || '').toString().toLowerCase().includes(searchQuery) ||
                              (record.vehicle_name || '').toString().toLowerCase().includes(searchQuery);
        const matchesMap = !mapFilter || record.map_name === mapFilter;
        const matchesVehicle = !vehicleFilter || record.vehicle_name === vehicleFilter;
        return matchesSearch && matchesMap && matchesVehicle;
    });

    if (!filteredData || filteredData.length === 0) {
        alert('No records to export. Please check your filters.');
        return;
    }

    downloadCSV(filteredData);
}

// ...existing code...

(function(){
  function closeMenu() {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (!btn || !menu) return;
    btn.setAttribute('aria-expanded','false');
    menu.setAttribute('aria-hidden','true');
    document.body.classList.remove('mobile-menu-open');
  }
  function openMenu() {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (!btn || !menu) return;
    btn.setAttribute('aria-expanded','true');
    menu.setAttribute('aria-hidden','false');
    document.body.classList.add('mobile-menu-open');
  }
  window.toggleMobileMenu = function(){
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (!btn || !menu) return;
    const open = btn.getAttribute('aria-expanded') === 'true';
    if (open) closeMenu(); else openMenu();
  };

  document.addEventListener('click', function(e){
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (!btn || !menu) return;
    if (btn.contains(e.target) || menu.contains(e.target)) return;
    if (btn.getAttribute('aria-expanded') === 'true') closeMenu();
  });

  window.addEventListener('resize', function(){
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (!btn || !menu) return;
    if (window.innerWidth >= 800) {
      // ensure menu visible on desktop
      menu.removeAttribute('aria-hidden');
      btn.setAttribute('aria-expanded','false');
      document.body.classList.remove('mobile-menu-open');
    } else {
      // keep it closed by default on small screens
      menu.setAttribute('aria-hidden','true');
      btn.setAttribute('aria-expanded','false');
      document.body.classList.remove('mobile-menu-open');
    }
  });

  // initialize state on load
  document.addEventListener('DOMContentLoaded', function(){
    const menu = document.getElementById('mobile-menu');
    const btn = document.getElementById('mobile-menu-btn');
    if (!menu || !btn) return;
    if (window.innerWidth >= 800) {
      menu.removeAttribute('aria-hidden');
      btn.setAttribute('aria-expanded','false');
    } else {
      menu.setAttribute('aria-hidden','true');
      btn.setAttribute('aria-expanded','false');
    }
  });
})();
