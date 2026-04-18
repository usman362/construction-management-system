@extends('layouts.app')

@section('title', 'Create Timesheet')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Timesheets</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Create Timesheet</h1>

        <form method="POST" action="{{ route('timesheets.store') }}" x-data="timesheetForm()" @submit.prevent="submitForm">
            @csrf

            <!-- Assignment Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Assignment</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                        <select name="employee_id" id="employee_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('employee_id') border-red-500 @enderror">
                            <option value="">Select Employee</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">Project *</label>
                        <select name="project_id" id="project_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('project_id') border-red-500 @enderror">
                            <option value="">Select Project</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="crew_id" class="block text-sm font-medium text-gray-700 mb-2">Crew (Optional)</label>
                        <select name="crew_id" id="crew_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Select Crew</option>
                            @foreach ($crews as $crew)
                                <option value="{{ $crew->id }}" {{ old('crew_id') == $crew->id ? 'selected' : '' }}>
                                    {{ $crew->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('crew_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cost_code_id" class="block text-sm font-medium text-gray-700 mb-2">Phase Code</label>
                        <select name="cost_code_id" id="cost_code_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($costCodes ?? [] as $cc)
                                <option value="{{ $cc->id }}" {{ old('cost_code_id') == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->code }} — {{ $cc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="cost_type_id" class="block text-sm font-medium text-gray-700 mb-2">Cost Type</label>
                        <select name="cost_type_id" id="cost_type_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($costTypes ?? [] as $ct)
                                <option value="{{ $ct->id }}" {{ old('cost_type_id') == $ct->id ? 'selected' : '' }}>
                                    {{ $ct->code }} — {{ $ct->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                        <input type="date" name="date" id="date" required value="{{ old('date') }}" class="w-full border-gray-300 rounded-lg shadow-sm @error('date') border-red-500 @enderror">
                        @error('date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="shift_id" class="block text-sm font-medium text-gray-700 mb-2">Shift *</label>
                        <select name="shift_id" id="shift_id" required class="w-full border-gray-300 rounded-lg shadow-sm @error('shift_id') border-red-500 @enderror">
                            <option value="">Select Shift</option>
                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                    {{ $shift->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('shift_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Hours Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Hours</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="regular_hours" class="block text-sm font-medium text-gray-700 mb-2">Regular Hours *</label>
                        <input type="number" name="regular_hours" id="regular_hours" step="0.5" required value="{{ old('regular_hours', 0) }}"
                               @input="calculateTotal()" class="w-full border-gray-300 rounded-lg shadow-sm @error('regular_hours') border-red-500 @enderror">
                        @error('regular_hours')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="overtime_hours" class="block text-sm font-medium text-gray-700 mb-2">Overtime Hours *</label>
                        <input type="number" name="overtime_hours" id="overtime_hours" step="0.5" required value="{{ old('overtime_hours', 0) }}"
                               @input="calculateTotal()" class="w-full border-gray-300 rounded-lg shadow-sm @error('overtime_hours') border-red-500 @enderror">
                        @error('overtime_hours')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="double_time_hours" class="block text-sm font-medium text-gray-700 mb-2">Double Time Hours *</label>
                        <input type="number" name="double_time_hours" id="double_time_hours" step="0.5" required value="{{ old('double_time_hours', 0) }}"
                               @input="calculateTotal()" class="w-full border-gray-300 rounded-lg shadow-sm @error('double_time_hours') border-red-500 @enderror">
                        @error('double_time_hours')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-3 pt-2 border-t-2">
                        <label for="total_hours" class="block text-sm font-medium text-gray-700 mb-2">Total Hours</label>
                        <input type="number" id="total_hours" disabled value="0" step="0.5" class="w-full border-gray-300 rounded-lg shadow-sm bg-gray-100">
                    </div>
                </div>
            </div>

            <!-- Site-Specific Fields -->
            <div class="mb-6 p-4 bg-amber-50 rounded-lg border border-amber-200">
                <h2 class="text-xl font-semibold mb-4">Site-Specific Fields</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gate Log Hours (Nucor)</label>
                        <input type="number" step="0.25" name="gate_log_hours" value="{{ old('gate_log_hours') }}" placeholder="e.g. 10.5" class="w-full border-gray-300 rounded-lg shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">From the Nucor gate log sheet.</p>
                    </div>
                    <div class="flex items-center pt-6">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="work_through_lunch" value="1" class="rounded border-gray-300 text-blue-600" {{ old('work_through_lunch') ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Worked through lunch (Nucor)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Client Sign-Off -->
            <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200" x-data="{ sigOpen: false }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xl font-semibold">Client Sign-Off</h2>
                    <button type="button" @click="sigOpen = !sigOpen" class="text-sm text-indigo-700 hover:text-indigo-900 font-medium">
                        <span x-text="sigOpen ? 'Close' : 'Capture Signature'"></span>
                    </button>
                </div>
                <div x-show="sigOpen" x-cloak class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Client Name / Representative</label>
                        <input type="text" name="client_signature_name" placeholder="Printed name" class="w-full border-gray-300 rounded-lg shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Signature</label>
                        <canvas id="sigCanvas" class="bg-white border border-gray-300 rounded-lg w-full" style="height:120px;touch-action:none"></canvas>
                        <div class="flex gap-2 mt-2">
                            <button type="button" onclick="sigClear()" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">Clear</button>
                            <button type="button" onclick="sigSave()" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded">Attach to Timesheet</button>
                            <span id="sigStatus" class="text-xs text-gray-500 self-center"></span>
                        </div>
                        <input type="hidden" name="client_signature" id="client_signature_input">
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Notes</h2>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <script>
            // Minimal signature pad (mouse + touch). Saves as base64 data URL
            // into a hidden input the backend accepts.
            (function(){
                var canvas = document.getElementById('sigCanvas');
                if (!canvas) return;
                var ctx = canvas.getContext('2d');
                var drawing = false, last = null;
                function resize(){
                    var r = canvas.getBoundingClientRect();
                    canvas.width = r.width; canvas.height = r.height;
                    ctx.strokeStyle = '#111'; ctx.lineWidth = 2; ctx.lineCap = 'round';
                }
                window.addEventListener('resize', resize);
                setTimeout(resize, 150);
                function pos(e){
                    var r = canvas.getBoundingClientRect();
                    var t = e.touches ? e.touches[0] : e;
                    return { x: t.clientX - r.left, y: t.clientY - r.top };
                }
                function down(e){ drawing = true; last = pos(e); e.preventDefault(); }
                function move(e){
                    if (!drawing) return;
                    var p = pos(e);
                    ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
                    last = p; e.preventDefault();
                }
                function up(){ drawing = false; last = null; }
                canvas.addEventListener('mousedown', down); canvas.addEventListener('mousemove', move);
                canvas.addEventListener('mouseup', up);    canvas.addEventListener('mouseout', up);
                canvas.addEventListener('touchstart', down); canvas.addEventListener('touchmove', move);
                canvas.addEventListener('touchend', up);
                window.sigClear = function(){
                    ctx.clearRect(0,0,canvas.width,canvas.height);
                    document.getElementById('client_signature_input').value = '';
                    document.getElementById('sigStatus').textContent = '';
                };
                window.sigSave = function(){
                    document.getElementById('client_signature_input').value = canvas.toDataURL('image/png');
                    document.getElementById('sigStatus').textContent = '✓ Signature attached';
                };
            })();
            </script>

            <!-- Form Actions -->
            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Create Timesheet
                </button>
                <a href="{{ route('timesheets.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function timesheetForm() {
        return {
            calculateTotal() {
                const regular = parseFloat(document.getElementById('regular_hours').value) || 0;
                const overtime = parseFloat(document.getElementById('overtime_hours').value) || 0;
                const doubleTime = parseFloat(document.getElementById('double_time_hours').value) || 0;
                const total = regular + overtime + doubleTime;
                document.getElementById('total_hours').value = total.toFixed(2);
            },
            submitForm() {
                this.calculateTotal();
                document.querySelector('form').submit();
            }
        }
    }
</script>
@endsection
