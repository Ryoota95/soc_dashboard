<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agent {{ $name }} - SOC Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen">
<div class="max-w-7xl mx-auto p-6 space-y-6">

    <div class="flex items-center gap-4">
        <a href="/" class="text-slate-400 hover:text-sky-400 text-sm">← Dashboard</a>
        <h1 class="text-2xl font-bold">Agent: {{ $name }}</h1>
    </div>

    <section class="bg-slate-800 rounded-lg p-4">
        <div id="info" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
    </section>

    <section class="bg-slate-800 rounded-lg p-4">
        <h2 class="font-semibold mb-3">System inventory</h2>
        <div id="inventory" class="grid grid-cols-2 md:grid-cols-5 gap-4"></div>
    </section>

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="bg-slate-800 rounded-lg p-4 lg:col-span-2">
            <h2 class="font-semibold mb-3">Events count evolution <span class="text-slate-400 text-sm font-normal">(24 jam terakhir)</span></h2>
            <canvas id="eventsChart" height="110"></canvas>
        </section>
        <section class="bg-slate-800 rounded-lg p-4">
            <h2 class="font-semibold mb-3">MITRE ATT&amp;CK <span class="text-slate-400 text-sm font-normal">Top tactics</span></h2>
            <div id="mitre"></div>
        </section>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="bg-slate-800 rounded-lg p-4">
            <div class="flex items-center mb-3">
                <h2 class="font-semibold mr-auto">Compliance</h2>
                <select id="comp-select" class="bg-slate-700 rounded px-2 py-1 text-sm"></select>
            </div>
            <canvas id="compChart" height="200"></canvas>
        </section>
        <section class="bg-slate-800 rounded-lg p-4 lg:col-span-2">
            <h2 class="font-semibold mb-3">Vulnerability Detection</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div id="vuln-cards" class="grid grid-cols-2 gap-3"></div>
                <div>
                    <p class="text-sm font-semibold mb-2">Top 5 Packages</p>
                    <table class="w-full text-sm">
                        <tbody id="vuln-pkgs"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <section class="bg-slate-800 rounded-lg p-4">
        <h2 class="font-semibold mb-3">Security Configuration Assessment</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="px-3 py-2">Policy</th><th class="px-3 py-2">Scan terakhir</th>
                        <th class="px-3 py-2">Passed</th><th class="px-3 py-2">Failed</th>
                        <th class="px-3 py-2">Invalid</th><th class="px-3 py-2">Score</th>
                    </tr>
                </thead>
                <tbody id="sca-body"></tbody>
            </table>
        </div>
    </section>

    <section class="bg-slate-800 rounded-lg p-4">
        <h2 class="font-semibold mb-3">FIM: Recent events</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="px-3 py-2">Waktu</th><th class="px-3 py-2">Path</th>
                        <th class="px-3 py-2">Action</th><th class="px-3 py-2">Rule</th>
                        <th class="px-3 py-2">Level</th><th class="px-3 py-2">Rule ID</th>
                    </tr>
                </thead>
                <tbody id="fim-body"></tbody>
            </table>
        </div>
    </section>
</div>

<script>
const AGENT = @json($name);
const palette = ['#34d399', '#60a5fa', '#f472b6', '#a78bfa', '#fbbf24'];
let eventsChart, compChart, compData = {};

function esc(s) {
    return String(s ?? '-').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
const fmtDate = t => t ? new Date(t).toLocaleString('id-ID') : '-';
const emptyRow = (cols, msg) => `<tr><td colspan="${cols}" class="px-3 py-6 text-center text-slate-400">${msg}</td></tr>`;

function kv(label, html) {
    return `<div><p class="text-slate-400 text-xs">${label}</p><p class="font-semibold break-words">${html}</p></div>`;
}

function statusBadge(s) {
    if (!s) return '-';
    const ok = String(s).toLowerCase() === 'active';
    return `<span class="inline-flex items-center gap-2"><span class="w-2 h-2 rounded-full ${ok ? 'bg-green-400' : 'bg-slate-500'}"></span>${esc(s)}</span>`;
}

// satuan memory belum pasti (byte/KB/MB), ditebak dari besar angkanya
function memText(v) {
    if (v === null || v === undefined || v === '') return '-';
    const n = Number(v);
    if (isNaN(n)) return esc(v);
    const gb = n > 1e8 ? n / 1073741824 : n > 1e5 ? n / 1048576 : n / 1024;
    return gb.toFixed(1) + ' GB';
}

function renderInfo(i) {
    i = i ?? {};
    document.getElementById('info').innerHTML = [
        kv('ID', esc(i.id)),
        kv('Status', statusBadge(i.status)),
        kv('IP address', esc(i.ip)),
        kv('Versi agent', esc(i.version)),
        kv('Operating system', esc(i.os)),
        kv('Kernel', esc(i.kernel)),
        kv('Terakhir terlihat', fmtDate(i.last_seen)),
        kv('Alert terakhir', fmtDate(i.last_alert)),
    ].join('');
}

function renderInventory(v) {
    v = v ?? {};
    document.getElementById('inventory').innerHTML = [
        kv('Cores', esc(v.cores)),
        kv('Memory', memText(v.memory)),
        kv('CPU', esc(v.cpu)),
        kv('Host name', esc(v.hostname)),
        kv('Serial number', esc(v.serial)),
    ].join('');
}

function renderEvents(events) {
    events = events ?? [];
    const labels = events.map(e => new Date(e.time).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'}));
    const data = events.map(e => e.total);
    if (!eventsChart) {
        eventsChart = new Chart(document.getElementById('eventsChart'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Alert', data, borderColor: '#34d399', backgroundColor: 'rgba(52,211,153,.15)', fill: true, tension: .3, pointRadius: 2 }] },
            options: { plugins: { legend: { display: false } } }
        });
    } else {
        eventsChart.data.labels = labels;
        eventsChart.data.datasets[0].data = data;
        eventsChart.update();
    }
}

function renderMitre(items) {
    const el = document.getElementById('mitre');
    if (!items || !items.length) { el.innerHTML = '<p class="text-slate-400 text-sm">Tidak ada data.</p>'; return; }
    el.innerHTML = items.map(m => `
        <div class="flex items-center justify-between py-2 border-b border-slate-700">
            <span>${esc(m.name)}</span>
            <span class="bg-slate-700 rounded px-2 py-0.5 text-sm">${esc(m.total)}</span>
        </div>`).join('');
}

function drawCompliance(key) {
    const items = compData[key]?.items ?? [];
    const labels = items.map(x => x.name);
    const data = items.map(x => x.total);
    if (!compChart) {
        compChart = new Chart(document.getElementById('compChart'), {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: palette }] },
            options: { plugins: { legend: { position: 'right', labels: { color: '#cbd5e1' } } } }
        });
    } else {
        compChart.data.labels = labels;
        compChart.data.datasets[0].data = data;
        compChart.update();
    }
}

function renderCompliance(c) {
    compData = c ?? {};
    const sel = document.getElementById('comp-select');
    const keys = Object.keys(compData);
    sel.innerHTML = keys.map(k => `<option value="${esc(k)}">${esc(compData[k].label)}</option>`).join('');
    drawCompliance(keys[0]);
}

function renderVuln(v) {
    v = v ?? {};
    const cards = [
        ['Critical', v.critical, 'text-red-400'],
        ['High', v.high, 'text-yellow-400'],
        ['Medium', v.medium, 'text-sky-400'],
        ['Low', v.low, 'text-teal-400'],
    ];
    document.getElementById('vuln-cards').innerHTML = cards.map(([l, n, c]) => `
        <div class="bg-slate-700 rounded p-3">
            <p class="text-2xl font-bold ${c}">${esc(n ?? 0)}</p>
            <p class="text-slate-300 text-sm">${l}</p>
        </div>`).join('');

    const pk = v.packages ?? [];
    document.getElementById('vuln-pkgs').innerHTML = pk.length
        ? pk.map(p => `<tr class="border-b border-slate-700"><td class="py-2 pr-3 break-all">${esc(p.name)}</td><td class="py-2 text-right">${esc(p.total)}</td></tr>`).join('')
        : emptyRow(2, 'Tidak ada data.');
}

function renderSca(rows) {
    document.getElementById('sca-body').innerHTML = rows && rows.length
        ? rows.map(r => `
            <tr class="border-b border-slate-700">
                <td class="px-3 py-3">${esc(r.policy)}</td>
                <td class="px-3 py-3 whitespace-nowrap">${fmtDate(r.end)}</td>
                <td class="px-3 py-3">${esc(r.passed)}</td>
                <td class="px-3 py-3">${esc(r.failed)}</td>
                <td class="px-3 py-3">${esc(r.invalid)}</td>
                <td class="px-3 py-3">${esc(r.score)}${r.score != null ? '%' : ''}</td>
            </tr>`).join('')
        : emptyRow(6, 'Belum ada data SCA.');
}

function renderFim(rows) {
    document.getElementById('fim-body').innerHTML = rows && rows.length
        ? rows.map(r => `
            <tr class="border-b border-slate-700">
                <td class="px-3 py-3 whitespace-nowrap">${fmtDate(r.time)}</td>
                <td class="px-3 py-3 break-all">${esc(r.path)}</td>
                <td class="px-3 py-3">${esc(r.action)}</td>
                <td class="px-3 py-3">${esc(r.rule)}</td>
                <td class="px-3 py-3">${esc(r.level)}</td>
                <td class="px-3 py-3">${esc(r.rule_id)}</td>
            </tr>`).join('')
        : emptyRow(6, 'No recent events');
}

async function load() {
    const res = await fetch('/api/agents/' + encodeURIComponent(AGENT));
    const d = await res.json();
    renderInfo(d.info);
    renderInventory(d.inventory);
    renderEvents(d.events);
    renderMitre(d.mitre);
    renderCompliance(d.compliance);
    renderVuln(d.vulnerability);
    renderSca(d.sca);
    renderFim(d.fim);
}

document.getElementById('comp-select').addEventListener('change', e => drawCompliance(e.target.value));
load();
</script>
</body>
</html>