<!-- Sidebar Navigation -->
<aside class="w-64 min-w-[256px] bg-gray-900 text-gray-100 overflow-y-auto flex flex-col" style="scrollbar-width: thin; scrollbar-color: #4b5563 #1f2937;">

    <!-- Logo / Brand -->
    <div class="px-5 py-5 border-b border-gray-800">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center text-sm font-bold text-white flex-shrink-0">
                BT
            </div>
            <div>
                <h2 class="text-base font-bold text-white leading-tight">BuildTrack</h2>
                <p class="text-[11px] text-gray-500 leading-tight">Construction Mgmt</p>
            </div>
        </a>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-6">

        <!-- Dashboard — Everyone -->
        <div>
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- PROJECTS — Everyone -->
        <div>
            <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Projects</p>
            <a href="{{ route('projects.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('projects.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M3.75 3v18m16.5-18v18M5.25 3h13.5M5.25 7.5h13.5M5.25 12h13.5M5.25 16.5h13.5"/>
                </svg>
                <span>Projects</span>
            </a>
        </div>

        <!-- WORKFORCE — Admin, PM, Accountant, Field (partial) -->
        @if(Auth::user()->canAccess('employees') || Auth::user()->canAccess('crews'))
        <div>
            <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Workforce</p>
            <div class="space-y-0.5">
                @if(Auth::user()->canAccess('employees'))
                <a href="{{ route('employees.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('employees.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                    <span>Employees</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('crafts'))
                <a href="{{ route('crafts.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('crafts.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384 3.408 1.382-5.964L2.3 8.222l6.092-.527L11.42 2.25l3.028 5.445 6.092.527-4.918 4.392 1.382 5.964-5.384-3.408z"/>
                    </svg>
                    <span>Crafts</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('crews'))
                <a href="{{ route('crews.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('crews.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                    <span>Crews</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('shifts'))
                <a href="{{ route('shifts.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('shifts.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Shifts</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- TIME & LABOR -->
        @if(Auth::user()->canAccess('timesheets') || Auth::user()->canAccess('payroll'))
        <div>
            <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Time & Labor</p>
            <div class="space-y-0.5">
                @if(Auth::user()->canAccess('timesheets'))
                <a href="{{ route('timesheets.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('timesheets.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <span>Timesheets</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('payroll'))
                <a href="{{ route('payroll.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('payroll.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                    </svg>
                    <span>Payroll</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- COSTING -->
        @if(Auth::user()->canAccess('cost-codes') || Auth::user()->canAccess('invoices'))
        <div>
            <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Costing</p>
            <div class="space-y-0.5">
                @if(Auth::user()->canAccess('cost-codes'))
                <a href="{{ route('cost-codes.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('cost-codes.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5"/>
                    </svg>
                    <span>Cost Codes</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('clients'))
                <a href="{{ route('clients.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('clients.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                    <span>Clients</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('invoices'))
                <a href="{{ route('invoices.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('invoices.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <span>Vendor Invoices</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- PROCUREMENT -->
        @if(Auth::user()->canAccess('purchase-orders') || Auth::user()->canAccess('equipment') || Auth::user()->canAccess('materials'))
        <div>
            <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Procurement</p>
            <div class="space-y-0.5">
                @if(Auth::user()->canAccess('purchase-orders'))
                <a href="{{ route('purchase-orders.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('purchase-orders.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                    </svg>
                    <span>Purchase Orders</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('vendors'))
                <a href="{{ route('vendors.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('vendors.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.142-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                    <span>Vendors</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('equipment'))
                <a href="{{ route('equipment.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('equipment.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384 3.408 1.382-5.964L2.3 8.222l6.092-.527L11.42 2.25l3.028 5.445 6.092.527-4.918 4.392 1.382 5.964z"/>
                    </svg>
                    <span>Equipment</span>
                </a>
                @endif
                @if(Auth::user()->canAccess('materials'))
                <a href="{{ route('materials.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('materials.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                    <span>Materials</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- BILLING -->
        @if(Auth::user()->canAccess('billing'))
        <div>
            <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Billing</p>
            <a href="{{ route('billing.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('billing.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                </svg>
                <span>Client Billing</span>
            </a>
        </div>
        @endif

        <!-- REPORTS -->
        @if(Auth::user()->canAccess('reports'))
        <div>
            <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Reports</p>
            <a href="{{ route('reports.timesheets') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('reports.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                <span>Timesheet Reports</span>
            </a>
            <p class="px-3 py-2 text-xs text-gray-600 italic">Project reports available within each project</p>
        </div>
        @endif

        <!-- USER MANAGEMENT — Admin only -->
        @if(Auth::user()->canAccess('users'))
        <div>
            <p class="px-3 mb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Administration</p>
            <a href="{{ route('users.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150 {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white font-medium' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>User Management</span>
            </a>
        </div>
        @endif

    </nav>

    <!-- User Info at Bottom -->
    <div class="px-4 py-3 border-t border-gray-800">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold">{{ substr(Auth::user()->name, 0, 1) }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-300 truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-gray-500">{{ Auth::user()->role_label }}</p>
            </div>
        </div>
    </div>
</aside>
