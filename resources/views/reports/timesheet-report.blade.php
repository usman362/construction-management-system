@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Timesheet Report</h1>

        <!-- Filters -->
        <div class="bg-gray-50 p-6 rounded-lg mb-8 border border-gray-200">
            <form method="GET" action="{{ route('reports.timesheets') }}" class="grid grid-cols-2 gap-6">
                <!-- Employee Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Employee</label>
                    <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Employees</option>
                        @foreach($employees ?? [] as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->first_name }} {{ $emp->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Project Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Project
                        <span class="text-xs font-normal text-gray-500">(required for Weekly view)</span>
                    </label>
                    <select name="project_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Projects</option>
                        @foreach($projects ?? [] as $proj)
                            <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>
                                {{ $proj->project_number ? $proj->project_number . ' — ' : '' }}{{ $proj->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date Range (Employee / Project / Date views)</label>
                    <div class="flex gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Week Ending (only used in Weekly view) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Week Ending (Weekly view — Sunday)</label>
                    <input type="date" name="week_ending" value="{{ request('week_ending', $weekly['week_ending']->format('Y-m-d') ?? '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Group By -->
                <div class="col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">View</label>
                    <div class="flex gap-4 flex-wrap">
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="weekly" {{ $groupBy === 'weekly' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm font-semibold text-blue-700">Weekly Timesheet (Matrix)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="employee" {{ $groupBy === 'employee' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm">By Employee</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="project" {{ $groupBy === 'project' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm">By Project</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="group_by" value="date" {{ $groupBy === 'date' ? 'checked' : '' }} class="w-4 h-4">
                            <span class="ml-2 text-sm">By Date</span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="col-span-2 flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Run Report
                    </button>
                </div>
            </form>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             WEEKLY MATRIX VIEW
             ═══════════════════════════════════════════════════════════════ --}}
        @if($groupBy === 'weekly' && $weekly)
            @php
                $project = $weekly['project'] ?? null;
                $client  = $project?->client;
                $weekEnding = $weekly['week_ending'];
                $days = $weekly['days'];
            @endphp

            @if(!$project)
                <div class="bg-amber-50 border border-amber-300 rounded-lg p-6 text-center">
                    <p class="text-amber-900 font-semibold">Select a project to view the weekly timesheet.</p>
                    <p class="text-amber-700 text-sm mt-1">The matrix view pivots hours by employee across Mon–Sun for a single project.</p>
                </div>
            @else
                <!-- Sheet header -->
                <div class="border border-gray-300 rounded-t-lg bg-blue-50 px-6 py-4 mb-0">
                    <div class="flex justify-between items-start flex-wrap gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 tracking-wide uppercase">Weekly Timesheet</h2>
                            <p class="text-sm text-gray-700 mt-1">
                                <span class="font-semibold">Client:</span> {{ $client->name ?? '—' }}
                            </p>
                            <p class="text-sm text-gray-700">
                                <span class="font-semibold">Project:</span>
                                {{ $project->project_number ?? '' }} &mdash; {{ $project->name }}
                            </p>
                        </div>
                        <div class="text-right text-sm">
                            <p><span class="font-semibold text-gray-700">Week Ending:</span> {{ $weekEnding->format('m/d/Y') }}</p>
                            @if($project->po_number)
                                <p><span class="font-semibold text-gray-700">Job / PO #:</span> {{ $project->po_number }}</p>
                            @endif
                            <p class="text-gray-600 text-xs mt-1">{{ $weekly['week_start']->format('M j') }} &ndash; {{ $weekEnding->format('M j, Y') }}</p>
                        </div>
                    </div>
                </div>

                @if(empty($weekly['shifts']))
                    <div class="border border-t-0 border-gray-300 rounded-b-lg bg-gray-50 p-10 text-center text-gray-500">
                        No timesheets recorded for this project during {{ $weekly['week_start']->format('M j') }} &ndash; {{ $weekEnding->format('M j, Y') }}.
                    </div>
                @else
                    {{-- Render one matrix block per shift --}}
                    @foreach($weekly['shifts'] as $shiftName => $shift)
                        <div class="overflow-x-auto border border-t-0 border-gray-300 {{ $loop->last ? 'rounded-b-lg' : '' }} mb-0">
                            <table class="w-full text-xs border-collapse">
                                <!-- Shift label band -->
                                <thead>
                                    <tr class="bg-gray-800 text-white uppercase tracking-widest">
                                        <td colspan="{{ 2 + (count($days) * 3) + 3 }}" class="px-4 py-2 text-center font-bold text-sm">
                                            {{ strtoupper($shiftName) }}
                                        </td>
                                    </tr>
                                    <!-- Day-of-week band -->
                                    <tr class="bg-blue-100 text-gray-800">
                                        <th class="border border-gray-300 px-2 py-2 text-left" rowspan="2" style="min-width:180px;">Employee Name</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left" rowspan="2" style="min-width:130px;">Classification</th>
                                        @foreach($days as $d)
                                            <th class="border border-gray-300 px-2 py-1 text-center" colspan="3">
                                                <div class="font-bold">{{ $d->format('D') }}</div>
                                                <div class="text-[10px] text-gray-600">{{ $d->format('m/d') }}</div>
                                            </th>
                                        @endforeach
                                        <th class="border border-gray-300 px-2 py-2 text-right bg-blue-200" rowspan="2">ST Total</th>
                                        <th class="border border-gray-300 px-2 py-2 text-right bg-blue-200" rowspan="2">OT Total</th>
                                        <th class="border border-gray-300 px-2 py-2 text-right bg-blue-200" rowspan="2">Per Diem</th>
                                    </tr>
                                    <!-- ST / OT / Per Diem sub-header -->
                                    <tr class="bg-blue-50 text-gray-700">
                                        @foreach($days as $d)
                                            <th class="border border-gray-300 px-1 py-1 text-[10px] font-semibold">ST</th>
                                            <th class="border border-gray-300 px-1 py-1 text-[10px] font-semibold">OT</th>
                                            <th class="border border-gray-300 px-1 py-1 text-[10px] font-semibold">P/D</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shift['employees'] as $i => $emp)
                                        <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                            <td class="border border-gray-300 px-2 py-1 font-semibold whitespace-nowrap">{{ $emp['name'] }}</td>
                                            <td class="border border-gray-300 px-2 py-1 text-gray-700">{{ $emp['classification'] ?: '—' }}</td>
                                            @foreach($days as $d)
                                                @php $cell = $emp['days'][$d->format('Y-m-d')] ?? ['st'=>0,'ot'=>0,'pd'=>0]; @endphp
                                                <td class="border border-gray-300 px-1 py-1 text-right tabular-nums {{ $cell['st'] > 0 ? '' : 'text-gray-300' }}">{{ $cell['st'] > 0 ? number_format($cell['st'], 1) : '—' }}</td>
                                                <td class="border border-gray-300 px-1 py-1 text-right tabular-nums {{ $cell['ot'] > 0 ? 'text-orange-600 font-semibold' : 'text-gray-300' }}">{{ $cell['ot'] > 0 ? number_format($cell['ot'], 1) : '—' }}</td>
                                                <td class="border border-gray-300 px-1 py-1 text-right tabular-nums {{ $cell['pd'] > 0 ? 'text-green-700' : 'text-gray-300' }}">{{ $cell['pd'] > 0 ? '$' . number_format($cell['pd'], 0) : '—' }}</td>
                                            @endforeach
                                            <td class="border border-gray-300 px-2 py-1 text-right bg-blue-50 font-semibold tabular-nums">{{ number_format($emp['st_total'], 1) }}</td>
                                            <td class="border border-gray-300 px-2 py-1 text-right bg-blue-50 font-semibold tabular-nums">{{ number_format($emp['ot_total'], 1) }}</td>
                                            <td class="border border-gray-300 px-2 py-1 text-right bg-blue-50 font-semibold tabular-nums">${{ number_format($emp['pd_total'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <!-- Shift totals -->
                                    <tr class="bg-blue-100 font-bold text-gray-800">
                                        <td class="border border-gray-300 px-2 py-2" colspan="2">{{ strtoupper($shiftName) }} TOTALS</td>
                                        @foreach($days as $d)
                                            @php $dt = $shift['day_totals'][$d->format('Y-m-d')] ?? ['st'=>0,'ot'=>0,'pd'=>0]; @endphp
                                            <td class="border border-gray-300 px-1 py-1 text-right tabular-nums">{{ number_format($dt['st'], 1) }}</td>
                                            <td class="border border-gray-300 px-1 py-1 text-right tabular-nums">{{ number_format($dt['ot'], 1) }}</td>
                                            <td class="border border-gray-300 px-1 py-1 text-right tabular-nums">{{ $dt['pd'] > 0 ? '$' . number_format($dt['pd'], 0) : '—' }}</td>
                                        @endforeach
                                        <td class="border border-gray-300 px-2 py-2 text-right tabular-nums">{{ number_format($shift['shift_st'], 1) }}</td>
                                        <td class="border border-gray-300 px-2 py-2 text-right tabular-nums">{{ number_format($shift['shift_ot'], 1) }}</td>
                                        <td class="border border-gray-300 px-2 py-2 text-right tabular-nums">${{ number_format($shift['shift_pd'], 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    <!-- Grand totals band (only if more than one shift) -->
                    @if(count($weekly['shifts']) > 1)
                        <div class="border border-t-0 border-gray-300 rounded-b-lg bg-gray-900 text-white px-6 py-3 mb-0 grid grid-cols-5 gap-4 text-sm">
                            <div class="font-bold uppercase tracking-widest">Grand Totals</div>
                            <div><span class="opacity-70">ST:</span> <span class="font-bold">{{ number_format($weekly['grand_st'], 1) }} hrs</span></div>
                            <div><span class="opacity-70">OT:</span> <span class="font-bold text-orange-300">{{ number_format($weekly['grand_ot'], 1) }} hrs</span></div>
                            <div><span class="opacity-70">Per Diem:</span> <span class="font-bold text-green-300">${{ number_format($weekly['grand_pd'], 2) }}</span></div>
                            <div class="text-right"><span class="opacity-70">Labor Cost:</span> <span class="font-bold">${{ number_format($weekly['grand_cost'], 2) }}</span></div>
                        </div>
                    @endif

                    <!-- Summary cards -->
                    <div class="grid grid-cols-4 gap-4 mt-8">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-5 rounded-lg border border-blue-200">
                            <p class="text-xs font-semibold text-gray-600 uppercase">Straight Time</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($weekly['grand_st'], 1) }} hrs</p>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-5 rounded-lg border border-orange-200">
                            <p class="text-xs font-semibold text-gray-600 uppercase">Overtime</p>
                            <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($weekly['grand_ot'], 1) }} hrs</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-lg border border-green-200">
                            <p class="text-xs font-semibold text-gray-600 uppercase">Per Diem</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">${{ number_format($weekly['grand_pd'], 2) }}</p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-5 rounded-lg border border-purple-200">
                            <p class="text-xs font-semibold text-gray-600 uppercase">Billable</p>
                            <p class="text-2xl font-bold text-purple-600 mt-1">${{ number_format($weekly['grand_billable'], 2) }}</p>
                        </div>
                    </div>

                    <!-- Signature blocks -->
                    <div class="grid grid-cols-2 gap-8 mt-10 print:mt-12">
                        <div>
                            <div class="border-b-2 border-gray-700 h-16"></div>
                            <p class="text-xs text-gray-600 mt-2 uppercase tracking-wide font-semibold">Company Representative Signature</p>
                            <div class="mt-3 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="border-b border-gray-400 h-6"></div>
                                    <p class="text-[10px] text-gray-500 mt-1 uppercase">Printed Name</p>
                                </div>
                                <div>
                                    <div class="border-b border-gray-400 h-6"></div>
                                    <p class="text-[10px] text-gray-500 mt-1 uppercase">Date</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="border-b-2 border-gray-700 h-16"></div>
                            <p class="text-xs text-gray-600 mt-2 uppercase tracking-wide font-semibold">Client Representative Signature</p>
                            <div class="mt-3 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="border-b border-gray-400 h-6"></div>
                                    <p class="text-[10px] text-gray-500 mt-1 uppercase">Printed Name</p>
                                </div>
                                <div>
                                    <div class="border-b border-gray-400 h-6"></div>
                                    <p class="text-[10px] text-gray-500 mt-1 uppercase">Date</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

        {{-- ═══════════════════════════════════════════════════════════════
             LEGACY EMPLOYEE / PROJECT / DATE VIEWS
             ═══════════════════════════════════════════════════════════════ --}}
        @else
            <!-- Summary Cards -->
            <div class="grid grid-cols-4 gap-4 mb-8">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
                    <p class="text-xs font-semibold text-gray-600 uppercase">Total Hours</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ number_format($summary['total_hours'] ?? 0, 1) }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200">
                    <p class="text-xs font-semibold text-gray-600 uppercase">Total Cost</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">${{ number_format($summary['total_cost'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200">
                    <p class="text-xs font-semibold text-gray-600 uppercase">Billable</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">${{ number_format($summary['total_billable'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-lg border border-orange-200">
                    <p class="text-xs font-semibold text-gray-600 uppercase">Avg Hours/Day</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2">{{ number_format($summary['avg_hours_per_day'] ?? 0, 1) }}</p>
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-blue-100 border border-gray-300">
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">Group</th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold">Detail</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Hours</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Labor Cost</th>
                            <th class="border border-gray-300 px-4 py-2 text-right font-bold">Billable</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalHours = 0;
                            $totalCost = 0;
                            $totalBillable = 0;
                            $rowClass = 0;
                        @endphp
                        @foreach($groupedData as $item)
                            @php
                                $totalHours += $item['hours'] ?? 0;
                                $totalCost += $item['labor_cost'] ?? 0;
                                $totalBillable += $item['billable_amount'] ?? 0;
                                $bgClass = $rowClass % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                                $rowClass++;
                            @endphp
                            <tr class="{{ $bgClass }} border border-gray-300">
                                <td class="border border-gray-300 px-4 py-2 font-semibold">{{ $item['group_name'] ?? 'N/A' }}</td>
                                <td class="border border-gray-300 px-4 py-2">{{ $item['detail'] ?? 'N/A' }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($item['hours'] ?? 0, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['labor_cost'] ?? 0, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($item['billable_amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-blue-100 border border-gray-300 font-bold">
                            <td colspan="2" class="border border-gray-300 px-4 py-2">TOTALS</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">{{ number_format($totalHours, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalCost, 2) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-right">${{ number_format($totalBillable, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Actions -->
        <div class="mt-8 flex gap-4 flex-wrap">
            <a href="{{ route('reports.timesheets.pdf', request()->query()) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
                Download PDF
            </a>
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                Print
            </button>
            <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
