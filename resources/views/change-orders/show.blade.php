@extends('layouts.app')

@section('title', 'Change Order Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('projects.change-orders.index', $project) }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Change Orders</a>
        <div class="space-x-2">
            <a href="{{ route('projects.change-orders.index', $project) }}?edit={{ $changeOrder->id }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</a>
            <form id="delete-change-order-form" method="POST" action="{{ route('projects.change-orders.destroy', [$project, $changeOrder]) }}" style="display:inline;">
                @csrf
                @method('DELETE')
            </form>
            <button type="button" onclick="confirmDelete('delete-change-order-form')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
        </div>
    </div>

    <!-- Header Section -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <!-- Project Info -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">PROJECT</h3>
                <p class="text-lg font-bold text-gray-900">{{ $project->name }}</p>
                <p class="text-sm text-gray-600">{{ $project->address ?? 'N/A' }}</p>
            </div>

            <!-- Purchase Order Info -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">PURCHASE ORDER</h3>
                <p class="text-lg font-bold text-gray-900">PO-{{ $project->id }}</p>
                <p class="text-sm text-gray-600">Contract Value: ${{ number_format($project->contract_value ?? 0, 2) }}</p>
            </div>

            <!-- Change Order Info -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">CHANGE ORDER</h3>
                <p class="text-lg font-bold text-gray-900">{{ $changeOrder->co_number }}</p>
                <p class="text-sm text-gray-600">{{ $changeOrder->date->format('M d, Y') }}</p>
                @php $pricingLabel = ($changeOrder->pricing_type ?? 'lump_sum') === 't_and_m' ? 'T & M' : 'Lump Sum'; @endphp
                <p class="text-xs mt-1">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ ($changeOrder->pricing_type ?? 'lump_sum') === 't_and_m' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800' }}">
                        {{ $pricingLabel }}
                    </span>
                </p>
                @if($changeOrder->client_po)
                    <p class="text-xs text-gray-500 mt-1">Client PO: <span class="font-semibold text-gray-700">{{ $changeOrder->client_po }}</span></p>
                @endif
            </div>
        </div>
    </div>

    <!-- Description of Work Section -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <h2 class="text-2xl font-bold mb-4">Description of Work</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">DESCRIPTION</h3>
                <p class="text-gray-900 whitespace-pre-wrap">{{ $changeOrder->description }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">SCOPE OF WORK</h3>
                <p class="text-gray-900 whitespace-pre-wrap">{{ $changeOrder->scope_of_work }}</p>
            </div>
        </div>
    </div>

    <!-- Financial Summary Section -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <h2 class="text-2xl font-bold mb-6">Financial Summary</h2>

        <div class="space-y-2">
            <div class="flex justify-between items-center border-b pb-2">
                <span class="font-semibold text-gray-700">0.1 Original Purchase Order Sum</span>
                <span class="font-bold text-gray-900">${{ number_format($project->contract_value ?? 0, 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2">
                <span class="font-semibold text-gray-700">0.2 Net change by previously authorized COs</span>
                <span class="font-bold text-gray-900">${{ number_format($previousCOsTotal ?? 0, 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2 bg-gray-50 p-2">
                <span class="font-semibold text-gray-700">0.3 Purchase Order Sum prior to this CO</span>
                <span class="font-bold text-gray-900">${{ number_format(($project->contract_value ?? 0) + ($previousCOsTotal ?? 0), 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2 text-blue-900 bg-blue-50 p-2">
                <span class="font-semibold">0.4 This CO amount {{ $changeOrder->amount >= 0 ? '(INCREASE)' : '(DECREASE)' }}</span>
                <span class="font-bold">${{ number_format($changeOrder->amount, 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2 bg-green-50 p-2">
                <span class="font-semibold text-green-900">0.5 New Purchase Order Sum</span>
                <span class="font-bold text-green-900">${{ number_format(($project->contract_value ?? 0) + ($previousCOsTotal ?? 0) + $changeOrder->amount, 2) }}</span>
            </div>

            <div class="flex justify-between items-center border-b pb-2">
                <span class="font-semibold text-gray-700">0.6 Contract Time change (days)</span>
                <span class="font-bold text-gray-900">{{ $changeOrder->contract_time_change_days ?? 0 }} days</span>
            </div>

            <div class="flex justify-between items-center pb-2">
                <span class="font-semibold text-gray-700">0.7 New completion date</span>
                <span class="font-bold text-gray-900">{{ $changeOrder->new_completion_date?->format('M d, Y') ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Previously Authorized COs Table -->
    @if ($previousCOs && $previousCOs->count())
        <div class="bg-white rounded-lg shadow p-8 mb-6">
            <h2 class="text-2xl font-bold mb-4">Previously Authorized Change Orders</h2>

            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">CO #</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($previousCOs as $priorCO)
                        <tr class="border-b">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $priorCO->co_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $priorCO->date->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right">${{ number_format($priorCO->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Service Job Breakdown Section -->
    <div class="bg-white rounded-lg shadow p-8 mb-6">
        <h2 class="text-2xl font-bold mb-6">Service Job Breakdown</h2>

        <!-- LABOR -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2">LABOR</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Skill</th>
                            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700">Amt Needed</th>
                            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700">Rate $/Hr</th>
                            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700">Hours/Day</th>
                            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700">Duration</th>
                            <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($changeOrder->laborDetails && $changeOrder->laborDetails->count())
                            @foreach ($changeOrder->laborDetails as $labor)
                                <tr class="border-b">
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $labor->skill }}</td>
                                    <td class="px-4 py-2 text-sm text-center text-gray-900">{{ $labor->amount_needed }}</td>
                                    <td class="px-4 py-2 text-sm text-center text-gray-900">${{ number_format($labor->rate, 2) }}</td>
                                    <td class="px-4 py-2 text-sm text-center text-gray-900">{{ $labor->hours_per_day }}</td>
                                    <td class="px-4 py-2 text-sm text-center text-gray-900">{{ $labor->duration }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900">${{ number_format($labor->cost, 2) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No labor items</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- EQUIPMENT -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2">EQUIPMENT</h3>
            <p class="text-gray-600">Equipment details would be displayed here.</p>
        </div>

        <!-- MATERIAL & CONSUMABLES -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2">MATERIAL & CONSUMABLES</h3>
            <p class="text-gray-600">Material and consumables details would be displayed here.</p>
        </div>

        <!-- OTHER COSTS -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2">OTHER COSTS</h3>
            <p class="text-gray-600">Other costs details would be displayed here.</p>
        </div>

        <!-- GRAND TOTAL -->
        <div class="bg-gray-900 text-white p-4 rounded mt-6 flex justify-between items-center">
            <span class="text-lg font-bold">GRAND TOTAL</span>
            <span class="text-2xl font-bold">${{ number_format($changeOrder->amount, 2) }}</span>
        </div>
    </div>

    <!-- Action Buttons -->
    @if (in_array($changeOrder->status, ['pending', 'draft', 'submitted']))
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Actions</h3>
            <div class="flex gap-4">
                <form method="POST" action="{{ route('projects.change-orders.approve', [$project, $changeOrder]) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                        Approve Change Order
                    </button>
                </form>
                <a href="{{ route('projects.change-orders.index', $project) }}?edit={{ $changeOrder->id }}" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-6 rounded">
                    Edit
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
