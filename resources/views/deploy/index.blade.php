<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deploy Dashboard - BuildTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #0f172a; color: #e2e8f0; font-family: 'Segoe UI', sans-serif; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; }
        .btn { transition: all 0.2s; cursor: pointer; }
        .btn:hover { transform: translateY(-1px); }
        .output-box { background: #0f172a; border: 1px solid #334155; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; max-height: 300px; overflow-y: auto; }
        .spinner { display: none; }
        .loading .spinner { display: inline-block; }
        .loading .btn-text { display: none; }
    </style>
</head>
<body class="min-h-screen p-6">
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
                <button onclick="runCommand('full-deploy')" class="btn bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">🚀 Full Deploy</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="runCommand('clear-cache')" class="btn bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">🧹 Clear Cache</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="runCommand('optimize')" class="btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">⚙️ Optimize</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="runCommand('db-check')" class="btn bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">🔌 DB Check</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
            </div>
        </div>

        <!-- Database Section -->
        <div class="card p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">🗄️ Database</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <button onclick="runCommand('migrate')" class="btn bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">▶ Migrate</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="runCommand('migrate-seed')" class="btn bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">▶ Migrate + Seed</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="runCommand('seed')" class="btn bg-teal-600 hover:bg-teal-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">🌱 Seed</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="runCommand('migration-status')" class="btn bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">📋 Migration Status</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="runCommand('rollback')" class="btn bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">↩ Rollback</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="if(confirm('⚠️ This will DROP ALL TABLES and re-migrate! Are you sure?')) runCommand('migrate-fresh')" class="btn bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">🔥 Fresh Migration</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
            </div>
        </div>

        <!-- System Section -->
        <div class="card p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">🔧 System</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <button onclick="runCommand('storage-link')" class="btn bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">🔗 Storage Link</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
                <button onclick="runCommand('key-generate')" class="btn bg-pink-600 hover:bg-pink-700 text-white px-4 py-3 rounded-lg text-sm font-medium">
                    <span class="btn-text">🔑 Key Generate</span>
                    <span class="spinner">⏳ Running...</span>
                </button>
            </div>
        </div>

        <!-- Output -->
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">📟 Output</h2>
                <button onclick="document.getElementById('output').innerHTML='Waiting for command...'" class="text-gray-400 hover:text-white text-sm">Clear</button>
            </div>
            <div id="output" class="output-box p-4 text-gray-300 whitespace-pre-wrap">Waiting for command...</div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-gray-500 text-sm">
            <p>⚠️ Remove or protect this page in production after setup is complete.</p>
        </div>
    </div>

    <script>
    function runCommand(command) {
        var outputEl = document.getElementById('output');
        outputEl.innerHTML = '⏳ Running ' + command + '...';

        // Disable all buttons
        document.querySelectorAll('.btn').forEach(function(b) { b.disabled = true; b.classList.add('opacity-50'); });

        fetch('/deploy/' + command, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var status = data.success ? '✅' : '❌';
            outputEl.innerHTML = status + ' ' + data.message + '\n\n' + (data.output || '');
            outputEl.style.color = data.success ? '#86efac' : '#fca5a5';
        })
        .catch(function(err) {
            outputEl.innerHTML = '❌ Error: ' + err.message;
            outputEl.style.color = '#fca5a5';
        })
        .finally(function() {
            document.querySelectorAll('.btn').forEach(function(b) { b.disabled = false; b.classList.remove('opacity-50'); });
        });
    }
    </script>
</body>
</html>
