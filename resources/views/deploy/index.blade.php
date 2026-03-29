<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Deploy Dashboard - BuildTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #0f172a; color: #e2e8f0; font-family: 'Segoe UI', sans-serif; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; }
        .btn { transition: all 0.2s; cursor: pointer; }
        .btn:hover { transform: translateY(-1px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .output-box { background: #0f172a; border: 1px solid #334155; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body class="min-h-screen p-4 md:p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">🚀 Deploy Dashboard</h1>
            <p class="text-gray-400">BuildTrack - Construction Management System</p>
            <a href="/" class="text-blue-400 hover:text-blue-300 text-sm mt-2 inline-block">← Back to App</a>
        </div>

        <!-- Quick Actions -->
        <div class="card p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">⚡ Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button onclick="run('full-deploy')" class="btn bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg text-sm font-medium">🚀 Full Deploy</button>
                <button onclick="run('clear-cache')" class="btn bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg text-sm font-medium">🧹 Clear Cache</button>
                <button onclick="run('optimize')" class="btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg text-sm font-medium">⚙️ Optimize</button>
                <button onclick="run('db-check')" class="btn bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg text-sm font-medium">🔌 DB Check</button>
            </div>
        </div>

        <!-- Git & Composer -->
        <div class="card p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">📦 Git & Composer</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <button onclick="run('git-pull')" class="btn bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-3 rounded-lg text-sm font-medium">⬇️ Git Pull</button>
                <button onclick="run('git-status')" class="btn bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-lg text-sm font-medium">📋 Git Status</button>
                <button onclick="run('composer-install')" class="btn bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-lg text-sm font-medium">📥 Composer Install</button>
            </div>
        </div>

        <!-- Database -->
        <div class="card p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">🗄️ Database</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <button onclick="run('migrate')" class="btn bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-lg text-sm font-medium">▶ Migrate</button>
                <button onclick="run('migrate-seed')" class="btn bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-lg text-sm font-medium">▶ Migrate + Seed</button>
                <button onclick="run('seed')" class="btn bg-teal-600 hover:bg-teal-700 text-white px-4 py-3 rounded-lg text-sm font-medium">🌱 Seed</button>
                <button onclick="run('migration-status')" class="btn bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-lg text-sm font-medium">📋 Migration Status</button>
                <button onclick="run('rollback')" class="btn bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-lg text-sm font-medium">↩ Rollback</button>
                <button onclick="if(confirm('⚠️ This will DROP ALL TABLES! Are you sure?')) run('migrate-fresh')" class="btn bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg text-sm font-medium">🔥 Fresh Migrate</button>
            </div>
        </div>

        <!-- System -->
        <div class="card p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">🔧 System</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <button onclick="run('storage-link')" class="btn bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-3 rounded-lg text-sm font-medium">🔗 Storage Link</button>
                <button onclick="run('key-generate')" class="btn bg-pink-600 hover:bg-pink-700 text-white px-4 py-3 rounded-lg text-sm font-medium">🔑 Key Generate</button>
            </div>
        </div>

        <!-- Output -->
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">📟 Output</h2>
                <button onclick="out.innerHTML='Waiting for command...'; out.style.color='#d1d5db'" class="text-gray-400 hover:text-white text-sm">Clear</button>
            </div>
            <div id="output" class="output-box p-4 text-gray-300 whitespace-pre-wrap">Waiting for command...</div>
        </div>

        <div class="text-center mt-6 text-gray-500 text-sm">
            ⚠️ Remove or protect this page after setup is complete.
        </div>
    </div>

<script>
var out = document.getElementById('output');
var token = document.querySelector('meta[name="csrf-token"]').content;

function run(cmd) {
    out.innerHTML = '⏳ Running: ' + cmd + ' ...\nPlease wait...';
    out.style.color = '#d1d5db';

    // Disable buttons
    var btns = document.querySelectorAll('.btn');
    btns.forEach(function(b) { b.disabled = true; });

    fetch('/deploy/' + cmd, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token
        }
    })
    .then(function(resp) {
        // Handle CSRF token mismatch (419)
        if (resp.status === 419) {
            out.innerHTML = '❌ CSRF token expired. Please refresh the page and try again.';
            out.style.color = '#fca5a5';
            return null;
        }
        return resp.json();
    })
    .then(function(data) {
        if (!data) return;
        var icon = data.success ? '✅' : '❌';
        out.innerHTML = icon + ' ' + data.message + '\n\n' + (data.output || '');
        out.style.color = data.success ? '#86efac' : '#fca5a5';
    })
    .catch(function(err) {
        out.innerHTML = '❌ Network Error: ' + err.message + '\n\nCheck if the server is running.';
        out.style.color = '#fca5a5';
    })
    .finally(function() {
        btns.forEach(function(b) { b.disabled = false; });
        // Scroll output to bottom
        out.scrollTop = out.scrollHeight;
    });
}
</script>
</body>
</html>
