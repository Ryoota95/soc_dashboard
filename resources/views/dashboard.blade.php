<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SOC Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen">
<div class="max-w-7xl mx-auto p-6">

    <h1 class="text-2xl font-bold mb-6">🛡️ SOC Security Dashboard</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-slate-800 p-4 rounded-lg">
            <p class="text-slate-400 text-sm">Total Alert</p>
            <p id="kpi-total" class="text-3xl font-bold">-</p>
        </div>
        <div class="bg-slate-800 p-4 rounded-lg">
            <p class="text-slate-400 text-sm">Critical (level 12+)</p>
            <p id="kpi-critical" class="text-3xl font-bold text-red-400">-</p>
        </div>
        <div class="bg-slate-800 p-4 rounded-lg">
            <p class="text-slate-400 text-sm">High (level 7-11)</p>
            <p id="kpi-high" class="text-3xl font-bold text-orange-400">-</p>
        </div>
        <div class="bg-slate-800 p-4 rounded-lg">
            <p class="text-slate-400 text-sm">Agent Aktif</p>
            <p id="kpi-agents" class="text-3xl font-bold text-green-400">-</p>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="bg-slate-800 p-4 rounded-lg md:col-span-2">
            <h2 class="font-semibold mb-2">Alert per Hari</h2>
            <canvas id="timelineChart" height="120"></canvas>
        </div>
        <div class="bg-slate-800 p-4 rounded-lg">
            <h2 class="font-semibold mb-2">Severity</h2>
            <canvas id="severityChart"></canvas>
        </div>
    </div>

    <div class="bg-slate-800 p-4 rounded-lg mb-6">
        <h2 class="font-semibold mb-2">Top Rule</h2>
        <canvas id="attackChart" height="80"></canvas>
    </div>

    <div class="bg-slate-800 p-4 rounded-lg">
        <div class="flex flex-wrap gap-2 items-center mb-4">
            <h2 class="font-semibold mr-auto">Recent Alerts</h2>
            <select id="filter-severity" class="bg-slate-700 rounded px-2 py-1 text-sm">
                <option value="">Semua severity</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="py-2">Waktu</th>
                        <th>Agent</th>
                        <th>Rule</th>
                        <th>Level</th>
                        <th>Severity</th>
                    </tr>
                </thead>
                <tbody id="alert-body"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
const sevColor = {
    critical: 'bg-red-600', high: 'bg-orange-500',
    medium: 'bg-yellow-500 text-black', low: 'bg-blue-500'
};
let timelineChart, severityChart, attackChart;

function esc(s) {
    return String(s ?? '-').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function makeCharts() {
    timelineChart = new Chart(document.getElementById('timelineChart'), {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Alert', data: [], borderColor: '#38bdf8', tension: .3 }] },
        options: { plugins: { legend: { display: false } } }
    });
    severityChart = new Chart(document.getElementById('severityChart'), {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] }
    });
    attackChart = new Chart(document.getElementById('attackChart'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Jumlah', data: [], backgroundColor: '#f472b6' }] },
        options: { plugins: { legend: { display: false } } }
    });
}

async function loadData() {
    const params = new URLSearchParams({
        severity: document.getElementById('filter-severity').value,
    });
    const res = await fetch('/api/dashboard?' + params);
    const d = await res.json();

    document.getElementById('kpi-total').textContent = d.summary.total ?? '-';
    document.getElementById('kpi-critical').textContent = d.summary.critical ?? '-';
    document.getElementById('kpi-high').textContent = d.summary.high ?? '-';
    document.getElementById('kpi-agents').textContent = d.summary.agents ?? '-';

    timelineChart.data.labels = d.timeline.map(x => x.date);
    timelineChart.data.datasets[0].data = d.timeline.map(x => x.total);
    timelineChart.update();

    const order = ['critical', 'high', 'medium', 'low'];
    const colors = { critical: '#dc2626', high: '#f97316', medium: '#eab308', low: '#3b82f6' };
    severityChart.data.labels = order;
    severityChart.data.datasets[0].data = order.map(k => d.severity[k] ?? 0);
    severityChart.data.datasets[0].backgroundColor = order.map(k => colors[k]);
    severityChart.update();

    attackChart.data.labels = d.attacks.map(x => x.attack_type);
    attackChart.data.datasets[0].data = d.attacks.map(x => x.total);
    attackChart.update();

    document.getElementById('alert-body').innerHTML = d.alerts.map(a => `
        <tr class="border-b border-slate-700">
            <td class="py-2">${new Date(a.detected_at).toLocaleString('id-ID')}</td>
            <td>${esc(a.agent)}</td>
            <td>${esc(a.rule)}</td>
            <td>${esc(a.level)}</td>
            <td><span class="px-2 py-0.5 rounded text-xs ${sevColor[a.severity] ?? ''}">${esc(a.severity)}</span></td>
        </tr>`).join('');
}

makeCharts();
loadData();
setInterval(loadData, 10000);
document.getElementById('filter-severity').addEventListener('change', loadData);
</script>
</body>
</html>