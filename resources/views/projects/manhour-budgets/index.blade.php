@extends('layouts.app')

@section('title', 'Manhour Budget - ' . $project->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">&larr; Back to {{ $project->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Manhour Budget</h1>
        </div>
        <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add Budget Entry
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Budget Hours</h3>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($manhourBudgets->sum('budget_hours'), 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Earned Hours</h3>
            <p class="text-3xl font-bold text-green-600">{{ number_format($manhourBudgets->sum('earned_hours'), 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Variance</h3>
            @php $variance = $manhourBudgets->sum('budget_hours') - $manhourBudgets->sum('earned_hours'); @endphp
            <p class="text-3xl font-bold {{ $variance >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($variance, 2) }}</p>
        </div>
    </div>

    <!-- Manhour Budget Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Phase Code</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Category</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Budget Hours</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Earned Hours</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Variance</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($manhourBudgets as $budget)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $budget->costCode ? $budget->costCode->code . ' - ' . $budget->costCode->name : '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $budget->category === 'direct' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ ucfirst($budget->category) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-900">{{ number_format($budget->budget_hours, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-900">{{ number_format($budget->earned_hours, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium {{ ($budget->budget_hours - $budget->earned_hours) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($budget->budget_hours - $budget->earned_hours, 2) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="editBudget({{ $budget->id }}, '{{ $budget->cost_code_id }}', '{{ $budget->category }}', '{{ $budget->budget_hours }}')" class="p-1 text-gray-400 hover:text-amber-600" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('projects.manhour-budgets.update', [$project, $budget]) }}" id="delete-form-{{ $budget->id }}" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No manhour budget entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Add Budget Entry</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="createForm" method="POST" action="{{ route('projects.manhour-budgets.store', $project) }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phase Code *</label>
                <select name="cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="">Select Phase Code</option>
                    @foreach(\App\Models\CostCode::orderBy('code')->get() as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->code }} - {{ $cc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="">Select Category</option>
                        <option value="direct">Direct</option>
                        <option value="indirect">Indirect</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Budget Hours *</label>
                    <input type="number" name="budget_hours" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="document.getElementById('createForm').submit()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Entry</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('editModal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Edit Budget Entry</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editForm" method="POST" action="" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phase Code *</label>
                <select name="cost_code_id" id="edit_cost_code_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="">Select Phase Code</option>
                    @foreach(\App\Models\CostCode::orderBy('code')->get() as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->code }} - {{ $cc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select name="category" id="edit_category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="">Select Category</option>
                        <option value="direct">Direct</option>
                        <option value="indirect">Indirect</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Budget Hours *</label>
                    <input type="number" name="budget_hours" id="edit_budget_hours" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button onclick="closeModal('editModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button onclick="document.getElementById('editForm').submit()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update Entry</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('createForm').reset();
    openModal('createModal');
}

function editBudget(id, costCodeId, category, budgetHours) {
    document.getElementById('editForm').action = window.BASE_URL+'/projects/{{ $project->id }}/manhour-budgets/' + id;
    document.getElementById('edit_cost_code_id').value = costCodeId || '';
    document.getElementById('edit_category').value = category || '';
    document.getElementById('edit_budget_hours').value = budgetHours || '';
    openModal('editModal');
}
</script>
@endpush

@endsection
