@extends('layouts.app')

@section('title', 'Payroll Period')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <a href="{{ route('payroll.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Payroll</a>
        <div class="space-x-2">
            <button type="button" onclick="editPeriod({{ $payrollPeriod->id }})" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
            <button type="button" onclick="confirmDelete('{{ route('payroll.destroy', $payrollPeriod) }}', null, '{{ route('payroll.index') }}')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $payrollPeriod->name ?: 'Payroll Period' }}</h1>
                <p class="text-lg text-gray-600 mt-2">
                    {{ $payrollPeriod->start_date?->format('M j, Y') ?? 'N/A' }} – {{ $payrollPeriod->end_date?->format('M j, Y') ?? 'N/A' }}
                </p>
            </div>
            <div class="text-right">
                @php
                    $statusClass = match ($payrollPeriod->status) {
                        'processed' => 'bg-green-100 text-green-800',
                        'open' => 'bg-blue-100 text-blue-800',
                        default => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $statusClass }}">
                    {{ ucfirst($payrollPeriod->status ?? 'draft') }}
                </span>
            </div>
        </div>

        <div class="flex gap-4 mb-8 flex-wrap">
            @if($payrollPeriod->status === 'open')
                <form method="POST" action="{{ route('payroll.generate', $payrollPeriod) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                        Generate Entries from Timesheets
                    </button>
                </form>

                @if($entries->isNotEmpty())
                    <form method="POST" action="{{ route('payroll.process', $payrollPeriod) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                            Process Payroll
                        </button>
                    </form>
                @endif
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-100 border border-gray-300">
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Employee</th>
                        <th class="border border-gray-300 px-4 py-2 text-left font-bold">Project</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Reg. Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">OT Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">DT Hrs</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Regular Pay</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">OT Pay</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Total Pay</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Billable</th>
                        <th class="border border-gray-300 px-4 py-2 text-right font-bold">Per Diem</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowClass = 0; @endphp
                    @forelse($entries as $entry)
                        @php
                            $emp = $entry->employee;
                            $empLabel = $emp ? trim($emp->first_name.' '.$emp->last_name) : 'N/A';
                            $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                            $rowClass++;
                        @endphp
                        <tr class="{{ $bgClass }} border border-gray-300">
                            <td class="border border-gray-300 px-4 py-2">{{ $empLabel }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $entry->project->name ?? '—' }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format((float) ($entry->regular_hours ?? 0), 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format((float) ($entry->overtime_hours ?? 0), 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format((float) ($entry->double_time_hours ?? 0), 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format((float) ($entry->regular_pay ?? 0), 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format((float) ($entry->overtime_pay ?? 0) + (float) ($entry->double_time_pay ?? 0), 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right font-bold">${{ number_format((float) ($entry->total_pay ?? 0), 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format((float) ($entry->billable_amount ?? 0), 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format((float) ($entry->per_diem ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr class="bg-gray-50 border border-gray-300">
                            <td colspan="10" class="border border-gray-300 px-4 py-4 text-center text-gray-500">
                                No payroll entries. Click &quot;Generate Entries from Timesheets&quot; to create them.
                            </td>
                        </tr>
                    @endforelse
                    @if($entries->isNotEmpty())
                        <tr class="bg-blue-100 border border-gray-300 font-bold">
                            <td colspan="2" class="border border-gray-300 px-4 py-2">TOTALS</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format((float) $entries->sum('regular_hours'), 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format((float) $entries->sum('overtime_hours'), 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format((float) $entries->sum('double_time_hours'), 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($entries->sum(fn ($e) => (float) ($e->regular_pay ?? 0)), 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($entries->sum(fn ($e) => (float) ($e->overtime_pay ?? 0) + (float) ($e->double_time_pay ?? 0)), 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($entries->sum(fn ($e) => (float) ($e->total_pay ?? 0)), 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($entries->sum(fn ($e) => (float) ($e->billable_amount ?? 0)), 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($entries->sum(fn ($e) => (float) ($e->per_diem ?? 0)), 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="mt-8">
            <a href="{{ route('payroll.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                Back to Payroll
            </a>
        </div>
    </div>
</div>

@include('payroll.partials.period-edit-modal')

@push('scripts')
<script>
var table = null;
function editPeriod(id){
    $.get('{{ url('/payroll') }}/' + id + '/edit', function(d){
        let f=document.getElementById('editForm');
        f.querySelector('#edit_id').value=d.id;
        f.querySelector('[name="name"]').value=d.name||'';
        f.querySelector('[name="start_date"]').value=d.start_date||'';
        f.querySelector('[name="end_date"]').value=d.end_date||'';
        document.getElementById('editSaveBtn').onclick=function(){ submitForm('editForm','{{ url('/payroll') }}/'+d.id,'PUT',table,'editModal'); };
        openModal('editModal');
    });
}
</script>
@endpush
@endsection
