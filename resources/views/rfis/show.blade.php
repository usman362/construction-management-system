@extends('layouts.app')
@section('title', $rfi->rfi_number . ' — ' . $rfi->subject)
@section('content')

@php
    $statusBadge = [
        'draft'     => 'bg-gray-100 text-gray-700 border-gray-200',
        'submitted' => 'bg-blue-100 text-blue-800 border-blue-200',
        'in_review' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'answered'  => 'bg-green-100 text-green-800 border-green-200',
        'closed'    => 'bg-slate-200 text-slate-800 border-slate-300',
    ];
    $priorityBadge = [
        'low'    => 'bg-gray-100 text-gray-700',
        'medium' => 'bg-blue-100 text-blue-700',
        'high'   => 'bg-amber-100 text-amber-800',
        'urgent' => 'bg-red-100 text-red-800',
    ];
@endphp

<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-800">← {{ $project->project_number }} {{ $project->name }}</a>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">
                <span class="font-mono text-gray-500">{{ $rfi->rfi_number }}</span> — {{ $rfi->subject }}
            </h1>
            <div class="flex items-center gap-2 mt-2">
                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded border {{ $statusBadge[$rfi->status] ?? 'bg-gray-100' }}">{{ $statusLabels[$rfi->status] ?? $rfi->status }}</span>
                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded {{ $priorityBadge[$rfi->priority] ?? 'bg-gray-100' }}">{{ $priorityLabels[$rfi->priority] ?? $rfi->priority }}</span>
                <span class="text-xs text-gray-500">{{ $categoryLabels[$rfi->category] ?? $rfi->category }}</span>
                @if($rfi->is_overdue)
                    <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-800">Overdue</span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if(!in_array($rfi->status, ['answered', 'closed']))
                <button type="button" onclick="document.getElementById('respondPanel').scrollIntoView({behavior:'smooth'})" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    Respond
                </button>
            @endif
            @if($rfi->status === 'answered')
                <button type="button" onclick="closeRfi()" class="inline-flex items-center gap-2 bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    Close RFI
                </button>
            @endif
            <button type="button" onclick="openEditRfi()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                Edit
            </button>
        </div>
    </div>

    <div id="rfiStatus" class="hidden rounded-lg p-3 text-sm"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Question -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-2">Question</h3>
                <div class="prose prose-sm max-w-none text-gray-800 whitespace-pre-line">{{ $rfi->question }}</div>
            </div>

            <!-- Response / Respond Form -->
            <div id="respondPanel" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-2">Response</h3>
                @if($rfi->response)
                    <div class="prose prose-sm max-w-none text-gray-800 whitespace-pre-line">{{ $rfi->response }}</div>
                    @if($rfi->cost_schedule_impact)
                        <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <div class="text-xs font-semibold text-amber-800 uppercase mb-1">Cost / Schedule Impact</div>
                            <div class="text-sm text-gray-800 whitespace-pre-line">{{ $rfi->cost_schedule_impact }}</div>
                        </div>
                    @endif
                    <div class="mt-3 text-xs text-gray-500">
                        Answered by {{ $rfi->responder->name ?? '—' }} on {{ $rfi->responded_date?->format('m/d/Y') ?? '—' }}
                    </div>
                @elseif(!in_array($rfi->status, ['closed']))
                    <form id="respondForm" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Response *</label>
                            <textarea name="response" rows="5" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cost / Schedule Impact (narrative)</label>
                            <textarea name="cost_schedule_impact" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="flex items-center gap-6">
                            <label class="inline-flex items-center text-sm text-gray-700">
                                <input type="checkbox" name="cost_impact" value="1" class="h-4 w-4 mr-2"> Cost Impact
                            </label>
                            <label class="inline-flex items-center text-sm text-gray-700">
                                <input type="checkbox" name="schedule_impact" value="1" class="h-4 w-4 mr-2"> Schedule Impact
                            </label>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" onclick="submitResponse()" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Submit Response</button>
                        </div>
                    </form>
                @else
                    <p class="text-sm text-gray-500">No response recorded.</p>
                @endif
            </div>

            <!-- Audit History -->
            @if($rfi->auditLogs && $rfi->auditLogs->count())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">History</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach($rfi->auditLogs->sortByDesc('created_at') as $log)
                            <li class="flex items-start gap-3 border-l-2 border-gray-200 pl-3">
                                <div class="flex-1">
                                    <div class="text-gray-900"><span class="font-semibold">{{ ucfirst($log->event) }}</span> by {{ $log->user->name ?? 'System' }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->created_at->format('M d, Y H:i') }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Details</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Submitted By</dt><dd class="text-gray-900">{{ $rfi->submitter->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Submitted</dt><dd class="text-gray-900">{{ $rfi->submitted_date?->format('m/d/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Assignee</dt><dd class="text-gray-900">{{ $rfi->assignee->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Needed By</dt>
                        <dd class="{{ $rfi->is_overdue ? 'text-red-600 font-semibold' : 'text-gray-900' }}">{{ $rfi->needed_by?->format('m/d/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-gray-500">Responded</dt><dd class="text-gray-900">{{ $rfi->responded_date?->format('m/d/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Closed</dt><dd class="text-gray-900">{{ $rfi->closed_date?->format('m/d/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Cost Impact</dt><dd class="text-gray-900">{{ $rfi->cost_impact ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Schedule Impact</dt><dd class="text-gray-900">{{ $rfi->schedule_impact ? 'Yes' : 'No' }}</dd></div>
                </dl>
            </div>

            {{-- RFI Document Attachments --}}
            @include('documents.partials.upload-panel', [
                'documentableType' => get_class($rfi),
                'documentableId'   => $rfi->id,
                'documents'        => $rfi->documents,
            ])
        </div>
    </div>
</div>

<!-- Edit RFI Modal -->
<div id="editRfiModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editRfiModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit RFI</h3>
            <button onclick="closeModal('editRfiModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editRfiForm" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                <input type="text" name="subject" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" value="{{ $rfi->subject }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Question *</label>
                <textarea name="question" rows="4" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $rfi->question }}</textarea>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        @foreach($statusLabels as $k => $l)
                            <option value="{{ $k }}" @selected($rfi->status === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        @foreach($priorityLabels as $k => $l)
                            <option value="{{ $k }}" @selected($rfi->priority === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        @foreach($categoryLabels as $k => $l)
                            <option value="{{ $k }}" @selected($rfi->category === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                    <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">— Unassigned —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected($rfi->assigned_to == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Submitted Date</label>
                    <input type="date" name="submitted_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" value="{{ $rfi->submitted_date?->format('Y-m-d') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Needed By</label>
                    <input type="date" name="needed_by" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" value="{{ $rfi->needed_by?->format('Y-m-d') }}">
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editRfiModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" id="editRfiSave" onclick="saveRfi()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const RFI_URL = window.BASE_URL + '/projects/{{ $project->id }}/rfis/{{ $rfi->id }}';

function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function showRfiStatus(kind, msg) {
    const el = document.getElementById('rfiStatus');
    el.textContent = msg;
    el.className = 'rounded-lg p-3 text-sm ' + (kind === 'ok' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800');
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 3500);
}

function openEditRfi() { openModal('editRfiModal'); }

function saveRfi() {
    const f = document.getElementById('editRfiForm');
    const fd = new FormData(f);
    const payload = {};
    fd.forEach((v, k) => { if (k !== '_token') payload[k] = v; });

    fetch(RFI_URL, {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
    .then(({ ok, body }) => {
        if (!ok) { showRfiStatus('error', body.message || 'Update failed.'); return; }
        closeModal('editRfiModal');
        showRfiStatus('ok', 'Updated. Reloading...');
        setTimeout(() => location.reload(), 800);
    });
}

function submitResponse() {
    const f = document.getElementById('respondForm');
    const fd = new FormData(f);
    const payload = {};
    fd.forEach((v, k) => { if (k !== '_token') payload[k] = v; });
    payload.cost_impact = f.querySelector('[name="cost_impact"]').checked ? 1 : 0;
    payload.schedule_impact = f.querySelector('[name="schedule_impact"]').checked ? 1 : 0;

    fetch(RFI_URL + '/respond', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
    .then(({ ok, body }) => {
        if (!ok) { showRfiStatus('error', body.message || 'Submit failed.'); return; }
        showRfiStatus('ok', 'Response submitted.');
        setTimeout(() => location.reload(), 800);
    });
}

function closeRfi() {
    if (!confirm('Close this RFI? This confirms the answer is acceptable.')) return;
    fetch(RFI_URL + '/close', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    })
    .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
    .then(({ ok, body }) => {
        if (!ok) { showRfiStatus('error', 'Close failed.'); return; }
        showRfiStatus('ok', 'RFI closed.');
        setTimeout(() => location.reload(), 800);
    });
}
</script>
@endpush

@endsection
