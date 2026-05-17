@extends('layouts.app')
@section('title', 'Daily Log — ' . $project->project_number)
@section('content')

{{--
    Mobile-first daily log entry — designed to be filled on a phone in 60 seconds.

    Features:
    - Camera capture: native phone camera via <input capture="environment">,
      multiple photos uploaded inline.
    - Auto-fill weather: geolocates the phone, hits OpenWeatherMap free tier
      via the project's lat/lng (or current GPS), populates weather/temperature.
      Falls back silently if no API key configured (Settings → weather_api_key).
    - Voice notes: browser SpeechRecognition for hands-free notes (Chrome/Edge).
    - Big touch targets — 44px minimum, mobile-optimized form rows.

    Posts to the same DailyLogController::store endpoint as the desktop form;
    photos uploaded as a follow-up POST to DocumentController::store via
    multipart/form-data so the daily log save itself stays small + fast.
--}}

<div class="max-w-xl mx-auto px-4 py-6 space-y-5">

    <div>
        <a href="{{ route('projects.daily-logs.index', $project) }}" class="text-sm text-blue-600 hover:underline">&larr; Back to logs</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Daily Log</h1>
        <p class="text-sm text-gray-500">{{ $project->project_number }} — {{ $project->name }}</p>
    </div>

    <div id="qcStatus" class="hidden rounded-lg p-3 text-sm"></div>

    {{-- 2026-05-12 (Brenda — Phase 2): AI Daily Log Generator.
         Big "speak it" button at the top of the page so the foreman never
         has to type on a phone keyboard. SpeechRecognition transcribes in
         the browser; the transcript is POSTed to /daily-logs/voice-parse;
         Groq Llama returns structured fields; we auto-fill every form
         input and surface a green banner with the summary. The form
         stays editable so the foreman can adjust before saving. --}}
    <div id="aiDictateCard" class="bg-gradient-to-br from-purple-600 via-fuchsia-600 to-pink-600 text-white rounded-xl shadow-md p-5">
        <div class="flex items-start gap-3 mb-3">
            <div class="flex-1">
                <h3 class="text-base font-bold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
                    AI Dictation
                    <span class="bg-yellow-400 text-purple-900 text-[10px] font-black px-1.5 py-0.5 rounded-full shadow">AI</span>
                </h3>
                <p class="text-xs text-purple-100 mt-0.5">Tap mic, say what happened today, AI fills the form.</p>
            </div>
        </div>
        <button type="button" id="aiDictateBtn" class="w-full bg-white text-purple-700 font-bold text-base py-4 rounded-lg shadow flex items-center justify-center gap-2 active:bg-purple-50 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/></svg>
            <span id="aiDictateLabel">Tap to start dictation</span>
        </button>
        <p id="aiDictateHint" class="text-[11px] text-purple-100 mt-2 leading-relaxed">
            Example: "Sunny day, around 80 degrees. Crew of 5 finished welding the tank skirt. Inspector from OSHA came by around 2. One near-miss when a hose snapped, no injuries. Sherwin delivery showed up at 10."
        </p>
        <div id="aiDictateTranscript" class="hidden mt-3 bg-white/10 backdrop-blur rounded-lg p-3 text-xs leading-relaxed"></div>
    </div>

    <form id="quickLogForm" class="space-y-4 bg-white rounded-xl shadow-sm border border-gray-200 p-5"
          enctype="multipart/form-data">
        @csrf

        {{-- Date — note the field name is `date` (not log_date) to match
             DailyLogController@rules(). --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
            <input type="date" name="date" required value="{{ now()->toDateString() }}"
                   class="w-full px-3 py-3 text-base border border-gray-300 rounded-lg">
        </div>

        {{-- Auto-fill weather button --}}
        <div class="grid grid-cols-3 gap-2">
            <button type="button" id="autoWeatherBtn" class="col-span-3 inline-flex items-center justify-center gap-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-sm font-semibold py-3 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                <span id="autoWeatherLabel">Auto-fill weather from GPS</span>
            </button>
        </div>

        {{-- Weather + temperature --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Weather</label>
                <select name="weather" id="weather" required class="w-full px-3 py-3 text-base border border-gray-300 rounded-lg">
                    <option value="">— Pick —</option>
                    @foreach(['sunny', 'cloudy', 'rainy', 'snowy', 'foggy', 'windy'] as $w)
                        <option value="{{ $w }}">{{ ucfirst($w) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Temp °F</label>
                <input type="number" name="temperature" id="temperature" required class="w-full px-3 py-3 text-base border border-gray-300 rounded-lg">
            </div>
        </div>

        {{-- Optional weather detail (hi/lo, precip, wind) — collapsible --}}
        <details class="bg-gray-50 rounded-lg border border-gray-200">
            <summary class="px-3 py-2 text-sm font-semibold text-gray-700 cursor-pointer">More weather details (optional)</summary>
            <div class="p-3 grid grid-cols-2 gap-2">
                <div><label class="block text-xs text-gray-600 mb-1">High °F</label><input type="number" name="temperature_high" id="temperature_high" class="w-full px-2 py-2 text-sm border border-gray-300 rounded"></div>
                <div><label class="block text-xs text-gray-600 mb-1">Low °F</label><input type="number" name="temperature_low" id="temperature_low" class="w-full px-2 py-2 text-sm border border-gray-300 rounded"></div>
                <div><label class="block text-xs text-gray-600 mb-1">Precip in</label><input type="number" step="0.01" name="precipitation" id="precipitation" class="w-full px-2 py-2 text-sm border border-gray-300 rounded"></div>
                <div><label class="block text-xs text-gray-600 mb-1">Wind mph</label><input type="number" name="wind_speed" id="wind_speed" class="w-full px-2 py-2 text-sm border border-gray-300 rounded"></div>
            </div>
        </details>

        {{-- Notes with voice-to-text --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-semibold text-gray-700">Notes</label>
                <button type="button" id="voiceBtn" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:text-blue-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/></svg>
                    <span id="voiceLabel">Voice</span>
                </button>
            </div>
            <textarea name="notes" id="notes" rows="4" placeholder="What got done today, deliveries, issues..."
                      class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"></textarea>
        </div>

        {{-- Safety counters --}}
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs text-gray-600 mb-1">Incidents</label>
                <input type="number" name="incidents_count" min="0" placeholder="0" class="w-full px-2 py-2 text-sm border border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Near misses</label>
                <input type="number" name="near_misses_count" min="0" placeholder="0" class="w-full px-2 py-2 text-sm border border-gray-300 rounded">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Visitors</label>
            <input type="text" name="visitors" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" placeholder="Inspector, owner rep, etc.">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Safety Issues</label>
            <textarea name="safety_issues" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" placeholder="Detail any incidents/near-misses"></textarea>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Delays / Disruptions</label>
            <textarea name="delays" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" placeholder="Weather delays, late deliveries, etc."></textarea>
        </div>

        {{-- Photo capture — native camera with capture attribute. The browser
             on Android/iOS opens the camera directly when this is clicked. --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Photos</label>
            <input type="file" id="photoInput" name="photos[]" accept="image/*" capture="environment" multiple
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg bg-gray-50">
            <p class="text-[11px] text-gray-500 mt-1">Tap to open camera. You can add multiple photos.</p>
            <div id="photoPreview" class="mt-2 grid grid-cols-3 gap-2"></div>
        </div>

        <button type="submit" id="submitBtn"
                class="w-full bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold text-lg py-4 rounded-xl shadow-md transition disabled:opacity-50">
            Save Log
        </button>
    </form>
</div>

@push('scripts')
<script>
const QC_PROJECT_ID = {{ $project->id }};
const QC_LAT = @json($project->latitude ?? null);
const QC_LNG = @json($project->longitude ?? null);
const QC_WEATHER_API_KEY = @json(\App\Models\Setting::get('weather_api_key', ''));
const QC_STORE_URL  = '{{ route("projects.daily-logs.store", $project) }}';
const QC_DOCS_URL   = '{{ route("documents.store") }}';
const QC_INDEX_URL  = '{{ route("projects.daily-logs.index", $project) }}';
const QC_AI_PARSE_URL = '{{ route("projects.daily-logs.voice-parse", $project) }}';

function showQc(kind, msg) {
    const el = document.getElementById('qcStatus');
    el.className = 'rounded-lg p-3 text-sm ' + (kind === 'ok'
        ? 'bg-green-50 border border-green-200 text-green-800'
        : 'bg-amber-50 border border-amber-200 text-amber-800');
    el.textContent = msg;
    el.classList.remove('hidden');
}

// ─── Auto-fill weather ────────────────────────────────────────────
document.getElementById('autoWeatherBtn').addEventListener('click', async () => {
    const label = document.getElementById('autoWeatherLabel');
    label.textContent = 'Getting GPS…';

    if (!QC_WEATHER_API_KEY) {
        showQc('warn', 'Weather auto-fill not configured. Ask your admin to add the OpenWeatherMap API key in Settings.');
        label.textContent = 'Auto-fill weather (not configured)';
        return;
    }

    let lat = QC_LAT, lng = QC_LNG;
    if (!lat || !lng) {
        // No project lat/lng — try the phone's GPS
        try {
            const pos = await new Promise((res, rej) =>
                navigator.geolocation.getCurrentPosition(res, rej, { enableHighAccuracy: true, timeout: 8000 })
            );
            lat = pos.coords.latitude;
            lng = pos.coords.longitude;
        } catch (e) {
            showQc('warn', 'Could not get GPS. Pick weather manually.');
            label.textContent = 'Auto-fill weather (no GPS)';
            return;
        }
    }

    label.textContent = 'Fetching weather…';
    try {
        // Free OpenWeatherMap "Current Weather" endpoint — imperial units returns °F
        const url = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lng}&units=imperial&appid=${QC_WEATHER_API_KEY}`;
        const r = await fetch(url);
        const data = await r.json();
        if (!r.ok) throw new Error(data.message || 'Weather API error');

        // Map OpenWeatherMap "main" condition → our enum.
        const cond = String(data.weather?.[0]?.main || '').toLowerCase();
        const map = { clear: 'sunny', clouds: 'cloudy', rain: 'rainy', drizzle: 'rainy',
                      thunderstorm: 'rainy', snow: 'snowy', mist: 'foggy', fog: 'foggy',
                      haze: 'foggy', smoke: 'foggy' };
        const mapped = map[cond] ?? 'cloudy';

        document.getElementById('weather').value = mapped;
        document.getElementById('temperature').value = Math.round(data.main.temp);
        if (data.main.temp_max) document.getElementById('temperature_high').value = Math.round(data.main.temp_max);
        if (data.main.temp_min) document.getElementById('temperature_low').value = Math.round(data.main.temp_min);
        if (data.wind?.speed)   document.getElementById('wind_speed').value = Math.round(data.wind.speed);
        if (data.rain?.['1h'])  document.getElementById('precipitation').value = (data.rain['1h'] / 25.4).toFixed(2);

        label.textContent = '✓ Auto-filled — ' + Math.round(data.main.temp) + '°F ' + mapped;
        showQc('ok', 'Weather filled in. You can adjust before saving.');
    } catch (e) {
        showQc('warn', 'Weather lookup failed: ' + e.message);
        label.textContent = 'Auto-fill weather (failed)';
    }
});

// ─── Voice-to-text notes (legacy — appends raw transcript to Notes only) ──
const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
const voiceBtn   = document.getElementById('voiceBtn');
const voiceLabel = document.getElementById('voiceLabel');
const notesEl    = document.getElementById('notes');

if (!SR) {
    voiceBtn.disabled = true;
    voiceBtn.title = 'Voice input not supported in this browser';
    voiceBtn.classList.add('opacity-50');
} else {
    let rec = null, listening = false;
    voiceBtn.addEventListener('click', () => {
        if (listening) { rec?.stop(); return; }
        rec = new SR();
        rec.lang = 'en-US';
        rec.interimResults = false;
        rec.continuous = true;
        rec.onresult = (e) => {
            for (let i = e.resultIndex; i < e.results.length; i++) {
                if (e.results[i].isFinal) {
                    notesEl.value += (notesEl.value ? ' ' : '') + e.results[i][0].transcript;
                }
            }
        };
        rec.onstart = () => { listening = true;  voiceLabel.textContent = 'Listening… tap to stop'; voiceBtn.classList.add('text-red-600'); };
        rec.onend   = () => { listening = false; voiceLabel.textContent = 'Voice'; voiceBtn.classList.remove('text-red-600'); };
        rec.onerror = (e) => { showQc('warn', 'Voice recognition error: ' + e.error); };
        rec.start();
    });
}

// ─── AI Dictation (Brenda — Phase 2, 2026-05-12) ──────────────────────
// Foreman taps mic, talks freely, taps again to stop. The transcript
// gets POSTed to /daily-logs/voice-parse → Groq → structured fields.
// Every form input is then auto-filled. Foreman reviews + saves.
const aiBtn        = document.getElementById('aiDictateBtn');
const aiLabel      = document.getElementById('aiDictateLabel');
const aiHint       = document.getElementById('aiDictateHint');
const aiTranscript = document.getElementById('aiDictateTranscript');

if (!SR) {
    aiBtn.disabled = true;
    aiBtn.classList.add('opacity-60');
    aiLabel.textContent = 'Voice not supported on this browser';
} else {
    let rec = null, listening = false, transcript = '';

    const setFieldIfPresent = (id, value) => {
        const el = document.getElementById(id) || document.querySelector(`[name="${id}"]`);
        if (!el) return;
        if (value === null || value === undefined || value === '') return;
        // For <select>, only set if the option exists
        if (el.tagName === 'SELECT') {
            const opt = Array.from(el.options).find(o => o.value === String(value));
            if (opt) el.value = String(value);
        } else {
            el.value = value;
        }
    };

    const fillFromAi = (fields) => {
        setFieldIfPresent('weather', fields.weather);
        setFieldIfPresent('temperature', fields.temperature);
        setFieldIfPresent('temperature_high', fields.temperature_high);
        setFieldIfPresent('temperature_low', fields.temperature_low);
        setFieldIfPresent('precipitation', fields.precipitation);
        setFieldIfPresent('wind_speed', fields.wind_speed);
        if (fields.notes)         setFieldIfPresent('notes', fields.notes);
        setFieldIfPresent('visitors',          fields.visitors);
        setFieldIfPresent('safety_issues',     fields.safety_issues);
        setFieldIfPresent('incidents_count',   fields.incidents_count);
        setFieldIfPresent('near_misses_count', fields.near_misses_count);
        setFieldIfPresent('delays',            fields.delays);
    };

    const startDictation = () => {
        transcript = '';
        aiTranscript.textContent = '';
        aiTranscript.classList.add('hidden');
        rec = new SR();
        rec.lang = 'en-US';
        rec.interimResults = true;
        rec.continuous = true;
        rec.onresult = (e) => {
            let interim = '';
            for (let i = e.resultIndex; i < e.results.length; i++) {
                const piece = e.results[i][0].transcript;
                if (e.results[i].isFinal) {
                    transcript += (transcript ? ' ' : '') + piece;
                } else {
                    interim += piece;
                }
            }
            aiTranscript.classList.remove('hidden');
            aiTranscript.textContent = transcript + (interim ? ' ' + interim : '');
        };
        rec.onstart = () => {
            listening = true;
            aiLabel.textContent = '🔴 Listening… tap to stop';
            aiBtn.classList.add('bg-red-50');
            aiHint.textContent = 'Talking… tap the button again when you\'re done.';
        };
        rec.onend = async () => {
            listening = false;
            aiBtn.classList.remove('bg-red-50');
            const text = transcript.trim();
            if (!text) {
                aiLabel.textContent = 'Tap to start dictation';
                aiHint.textContent = 'Didn\'t catch anything — try again, speak a little louder.';
                return;
            }
            aiLabel.textContent = 'AI is filling the form…';
            aiBtn.disabled = true;
            try {
                const r = await fetch(QC_AI_PARSE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ transcript: text }),
                });
                const data = await r.json();
                if (!r.ok || !data.success) {
                    throw new Error(data.message || 'AI service unavailable');
                }
                fillFromAi(data.fields || {});
                aiLabel.textContent = '✓ Filled — review & save';
                aiHint.textContent = data.summary || 'Review the form below and tap Save Log.';
                showQc('ok', 'AI filled the form from your dictation. Review and adjust before saving.');
            } catch (err) {
                aiLabel.textContent = 'AI failed — tap to retry';
                aiHint.textContent = err.message;
                showQc('warn', 'AI parse failed: ' + err.message);
            } finally {
                aiBtn.disabled = false;
            }
        };
        rec.onerror = (e) => {
            listening = false;
            aiBtn.classList.remove('bg-red-50');
            aiLabel.textContent = 'Tap to start dictation';
            aiHint.textContent = 'Voice error: ' + e.error + '. Tap to try again.';
        };
        rec.start();
    };

    aiBtn.addEventListener('click', () => {
        if (listening) { rec?.stop(); return; }
        startDictation();
    });
}

// ─── Photo preview ─────────────────────────────────────────────────
document.getElementById('photoInput').addEventListener('change', (e) => {
    const preview = document.getElementById('photoPreview');
    preview.innerHTML = '';
    Array.from(e.target.files).forEach((file, i) => {
        const url = URL.createObjectURL(file);
        const div = document.createElement('div');
        div.className = 'relative bg-gray-100 rounded-lg overflow-hidden aspect-square';
        div.innerHTML = `<img src="${url}" class="w-full h-full object-cover">
                         <span class="absolute top-1 right-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded">${i+1}</span>`;
        preview.appendChild(div);
    });
});

// ─── Submit handler — save log first, then upload photos ──────────
document.getElementById('quickLogForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true; btn.textContent = 'Saving…';

    try {
        const fd = new FormData(e.target);
        // Strip photos from the daily log POST — they go to /documents separately
        // because daily logs.store expects only the log fields, not files.
        const photoFiles = fd.getAll('photos[]').filter(f => f instanceof File && f.size > 0);
        fd.delete('photos[]');

        const r = await fetch(QC_STORE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            body: fd,
        });

        // The store endpoint redirects after success. We'll just check ok.
        if (!r.ok && r.status !== 302) {
            const t = await r.text();
            throw new Error(t.substring(0, 200));
        }

        // Find the just-created daily log id from the redirect URL or by lookup.
        // Simplest: re-fetch the latest daily log via a small helper endpoint.
        // (We skip that complexity by attaching photos to the project itself
        // so they show up in the gallery either way.)
        if (photoFiles.length > 0) {
            btn.textContent = `Uploading ${photoFiles.length} photo(s)…`;
            for (const file of photoFiles) {
                const docFd = new FormData();
                docFd.append('file', file);
                docFd.append('documentable_type', 'App\\\\Models\\\\Project');
                docFd.append('documentable_id', QC_PROJECT_ID);
                docFd.append('category', 'photo');
                docFd.append('title', 'Daily log ' + new Date().toLocaleDateString());
                await fetch(QC_DOCS_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: docFd,
                });
            }
        }

        showQc('ok', 'Log saved. Redirecting…');
        setTimeout(() => location.href = QC_INDEX_URL, 700);
    } catch (err) {
        showQc('warn', 'Save failed: ' + err.message);
        btn.disabled = false; btn.textContent = 'Save Log';
    }
});
</script>
@endpush

@endsection
