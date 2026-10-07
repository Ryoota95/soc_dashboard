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

    <h1 class="text-2xl font-bold mb-6"> SOC Security Dashboard</h1>

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
                <div class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm">
            <div class="flex items-center gap-3 text-slate-400">
                <span id="alert-info">-</span>
                <select id="alert-perpage" class="bg-slate-700 text-slate-100 rounded px-2 py-1">
                    <option value="10">10 / halaman</option>
                    <option value="20" selected>20 / halaman</option>
                    <option value="50">50 / halaman</option>
                </select>
            </div>
            <div id="alert-pager" class="flex items-center gap-1"></div>
        </div>
    </div>

        <!-- Vulnerabilities -->
    <div class="bg-slate-800 p-4 rounded-lg mt-6">
        <div class="flex flex-wrap gap-2 items-center mb-4">
            <h2 class="font-semibold mr-auto">Vulnerabilities</h2>
            <select id="filter-vuln" class="bg-slate-700 rounded px-2 py-1 text-sm">
                <option value="Critical">Critical</option>
                <option value="High">High</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
                <option value="">Semua</option>
            </select>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
            <div class="bg-slate-700 p-3 rounded"><p class="text-slate-400 text-xs">Total</p><p id="vuln-total" class="text-2xl font-bold">-</p></div>
            <div class="bg-slate-700 p-3 rounded"><p class="text-slate-400 text-xs">Critical</p><p id="vuln-critical" class="text-2xl font-bold text-red-400">-</p></div>
            <div class="bg-slate-700 p-3 rounded"><p class="text-slate-400 text-xs">High</p><p id="vuln-high" class="text-2xl font-bold text-orange-400">-</p></div>
            <div class="bg-slate-700 p-3 rounded"><p class="text-slate-400 text-xs">Medium</p><p id="vuln-medium" class="text-2xl font-bold text-yellow-400">-</p></div>
            <div class="bg-slate-700 p-3 rounded"><p class="text-slate-400 text-xs">Low</p><p id="vuln-low" class="text-2xl font-bold text-blue-400">-</p></div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                               <thead class="text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="px-3 py-2 w-10"></th>
                        <th class="px-3 py-2">Agent</th>
                        <th class="px-3 py-2">Package</th>
                        <th class="px-3 py-2">Versi</th>
                        <th class="px-3 py-2">CVE</th>
                        <th class="px-3 py-2">Severity</th>
                        <th class="px-3 py-2">Deskripsi</th>
                    </tr>
                </thead>
                <tbody id="vuln-body"></tbody>
            </table>
        </div>
    </div>
</div>
<!-- Modal detail vulnerability -->
<div id="vuln-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
     onclick="if (event.target === this) closeVuln()">
    <div class="bg-slate-800 rounded-lg w-full max-w-3xl max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-700">
            <h3 class="text-lg font-semibold">Vulnerability details</h3>
            <button onclick="closeVuln()" class="text-slate-400 hover:text-white text-xl leading-none">✕</button>
        </div>
        <div class="flex gap-4 px-4 pt-3 border-b border-slate-700">
            <button id="tab-table" onclick="switchVulnTab('table')"
                    class="pb-2 border-b-2 border-sky-400 text-sky-400">Table</button>
            <button id="tab-json" onclick="switchVulnTab('json')"
                    class="pb-2 border-b-2 border-transparent">JSON</button>
        </div>
        <div class="p-4 overflow-y-auto">
            <div id="vd-table-wrap">
                <table class="w-full text-sm"><tbody id="vd-table"></tbody></table>
            </div>
            <pre id="vd-json" class="hidden text-xs whitespace-pre-wrap break-words"></pre>
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
function agentLink(name) {
    if (!name) return '-';
    return `<a href="/agents/${encodeURIComponent(name)}" class="text-sky-400 hover:underline">${esc(name)}</a>`;
}

const vulnColor = {
    Critical: 'bg-red-600', High: 'bg-orange-500',
    Medium: 'bg-yellow-500 text-black', Low: 'bg-blue-500'
};

let vulnItems = [];

function flatten(obj, prefix = '', out = {}) {
    for (const [k, v] of Object.entries(obj ?? {})) {
        const key = prefix ? prefix + '.' + k : k;
        if (v !== null && typeof v === 'object' && !Array.isArray(v)) {
            flatten(v, key, out);
        } else {
            out[key] = Array.isArray(v) ? JSON.stringify(v) : v;
        }
    }
    return out;
}

function showVuln(i) {
    const raw = vulnItems[i]?.raw;
    if (!raw) return;
    const flat = flatten(raw);

    document.getElementById('vd-table').innerHTML = Object.keys(flat).sort().map(k => `
        <tr class="border-b border-slate-700 align-top">
            <td class="py-2 pr-4 font-mono text-xs text-slate-400 whitespace-nowrap">${esc(k)}</td>
            <td class="py-2 break-words">${esc(flat[k])}</td>
        </tr>`).join('');
    document.getElementById('vd-json').textContent = JSON.stringify(raw, null, 2);

    switchVulnTab('table');
    document.getElementById('vuln-modal').classList.remove('hidden');
}

function closeVuln() {
    document.getElementById('vuln-modal').classList.add('hidden');
}

function switchVulnTab(tab) {
    document.getElementById('vd-table-wrap').classList.toggle('hidden', tab !== 'table');
    document.getElementById('vd-json').classList.toggle('hidden', tab !== 'json');
    ['table', 'json'].forEach(t => {
        const on = t === tab;
        const el = document.getElementById('tab-' + t);
        el.classList.toggle('border-sky-400', on);
        el.classList.toggle('text-sky-400', on);
        el.classList.toggle('border-transparent', !on);
    });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeVuln(); });

async function loadVulns() {
    const params = new URLSearchParams({ severity: document.getElementById('filter-vuln').value });
    const res = await fetch('/api/vulnerabilities?' + params);
    const d = await res.json();

    document.getElementById('vuln-total').textContent = d.summary.total ?? '-';
    document.getElementById('vuln-critical').textContent = d.summary.critical ?? '-';
    document.getElementById('vuln-high').textContent = d.summary.high ?? '-';
    document.getElementById('vuln-medium').textContent = d.summary.medium ?? '-';
    document.getElementById('vuln-low').textContent = d.summary.low ?? '-';

    vulnItems = d.items;
    document.getElementById('vuln-body').innerHTML = d.items.map((v, i) => `
        <tr class="border-b border-slate-700 hover:bg-slate-700/40">
            <td class="px-3 py-3">
              <button onclick="showVuln(${i})" aria-label="Lihat detail" title="Lihat detail"
        class="p-1 rounded text-slate-400 hover:text-sky-400 hover:bg-slate-700">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
         stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M19.5 14.25v-9A2.25 2.25 0 0017.25 3h-9A2.25 2.25 0 006 5.25v13.5A2.25 2.25 0 008.25 21H12"/>
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 15.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M18 18.75L20.25 21"/>
    </svg>
</button>
            </td>
            <td class="px-3 py-3 whitespace-nowrap">${agentLink(v.agent)}</td>
            <td class="px-3 py-3 whitespace-nowrap">${esc(v.package)}</td>
            <td class="px-3 py-3 whitespace-nowrap">${esc(v.version)}</td>
            <td class="px-3 py-3 whitespace-nowrap">${esc(v.cve)}</td>
            <td class="px-3 py-3">
                <span class="px-2 py-0.5 rounded text-xs ${vulnColor[v.severity] ?? 'bg-slate-600'}">${esc(v.severity)}</span>
            </td>
            <td class="px-3 py-3">
                <div class="max-w-xs truncate text-slate-300" title="${esc(v.description)}">${esc(v.description)}</div>
            </td>
        </tr>`).join('');
}

let alertPage = 1;
let alertLastPage = 1;

function pageList(cur, last) {
    const set = new Set([1, last, cur - 2, cur - 1, cur, cur + 1, cur + 2]);
    const pages = [...set].filter(p => p >= 1 && p <= last).sort((a, b) => a - b);
    const out = [];
    pages.forEach((p, i) => {
        if (i > 0 && p - pages[i - 1] > 1) out.push('...');
        out.push(p);
    });
    return out;
}

function renderPager(cur, last) {
    const btn = (label, page, disabled = false, active = false) => `
        <button ${disabled ? 'disabled' : ''} onclick="goAlertPage(${page})"
            class="min-w-8 px-2 py-1 rounded ${active ? 'bg-sky-600 text-white' : 'bg-slate-700 hover:bg-slate-600'} ${disabled ? 'opacity-40 cursor-not-allowed' : ''}">${label}</button>`;

    let html = btn('‹', cur - 1, cur <= 1);
    pageList(cur, last).forEach(p => {
        html += p === '...'
            ? '<span class="px-1 text-slate-500">…</span>'
            : btn(p, p, false, p === cur);
    });
    html += btn('›', cur + 1, cur >= last);

    document.getElementById('alert-pager').innerHTML = html;
}

function goAlertPage(p) {
    if (p < 1 || p > alertLastPage) return;
    alertPage = p;
    loadAlerts();
}

async function loadAlerts() {
    const params = new URLSearchParams({
        page: alertPage,
        per_page: document.getElementById('alert-perpage').value,
        severity: document.getElementById('filter-severity').value,
    });
    const res = await fetch('/api/alerts?' + params);
    const d = await res.json();

    alertPage = d.page;
    alertLastPage = d.last_page;

    document.getElementById('alert-body').innerHTML = d.items.length
        ? d.items.map(a => `
            <tr class="border-b border-slate-700">
                <td class="px-3 py-3 whitespace-nowrap">${new Date(a.detected_at).toLocaleString('id-ID')}</td>
                <td class="px-3 py-3">${agentLink(a.agent)}</td>
                <td class="px-3 py-3">${esc(a.rule)}</td>
                <td class="px-3 py-3">${esc(a.level)}</td>
                <td class="px-3 py-3"><span class="px-2 py-0.5 rounded text-xs ${sevColor[a.severity] ?? ''}">${esc(a.severity)}</span></td>
            </tr>`).join('')
        : '<tr><td colspan="5" class="px-3 py-6 text-center text-slate-400">Tidak ada alert.</td></tr>';

    const from = d.total === 0 ? 0 : (d.page - 1) * d.per_page + 1;
    const to = Math.min(d.page * d.per_page, d.total);
    document.getElementById('alert-info').textContent = `${from}–${to} dari ${d.total.toLocaleString('id-ID')}`;
    renderPager(d.page, d.last_page);
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
    const colors = { critical: '#ec0000', high: '#fc7514', medium: '#ffc721', low: '#3381ff' };
    severityChart.data.labels = order;
    severityChart.data.datasets[0].data = order.map(k => d.severity[k] ?? 0);
    severityChart.data.datasets[0].backgroundColor = order.map(k => colors[k]);
    severityChart.update();

    attackChart.data.labels = d.attacks.map(x => x.attack_type);
    attackChart.data.datasets[0].data = d.attacks.map(x => x.total);
    attackChart.update();

    
}

makeCharts();
loadData();
setInterval(loadData, 10000);
loadVulns();
setInterval(loadVulns, 60000);
document.getElementById('filter-severity').addEventListener('change', () => { alertPage = 1; loadAlerts(); });
document.getElementById('alert-perpage').addEventListener('change', () => { alertPage = 1; loadAlerts(); });
loadAlerts();
setInterval(() => { if (alertPage === 1) loadAlerts(); }, 10000);
document.getElementById('filter-vuln').addEventListener('change', loadVulns);
</script>
</body>
</html>