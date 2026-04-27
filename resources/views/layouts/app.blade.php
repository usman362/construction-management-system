<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('/') }}">
    <script>window.BASE_URL = '{{ url('/') }}';</script>
    @php $appFavicon = \App\Models\Setting::get('favicon'); @endphp
    @if($appFavicon)
    <link rel="icon" href="{{ asset($appFavicon) }}" type="image/png">
    @endif
    <title>@yield('title', 'CMS') - {{ \App\Models\Setting::get('company_name', 'BuildTrack') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ── DataTables Clean Theme ── */
        table.dataTable{border-collapse:collapse!important;border-spacing:0!important;width:100%!important}
        table.dataTable thead th,table.dataTable thead td{border-bottom:none!important}
        table.dataTable.no-footer{border-bottom:none!important}
        .dataTables_wrapper{padding:1rem 1.25rem}
        .dataTables_wrapper .dataTables_length,.dataTables_wrapper .dataTables_filter{display:inline-flex;align-items:center;margin-bottom:0}
        .dataTables_wrapper .dataTables_length label,.dataTables_wrapper .dataTables_filter label{font-size:.8rem;color:#64748b;font-weight:500;display:flex;align-items:center;gap:.5rem;margin:0}
        .dataTables_wrapper .dataTables_length select{border:1px solid #e2e8f0;border-radius:.375rem;padding:.35rem 1.75rem .35rem .5rem;font-size:.8rem;background:#f8fafc;color:#334155;outline:none;cursor:pointer}
        .dataTables_wrapper .dataTables_length select:focus{border-color:#3b82f6;box-shadow:0 0 0 2px rgba(59,130,246,.15)}
        .dataTables_wrapper .dataTables_filter input{border:1px solid #e2e8f0;border-radius:.375rem;padding:.4rem .75rem .4rem 2rem;font-size:.8rem;background:#f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E") no-repeat .5rem center;background-size:.875rem;color:#334155;outline:none;width:200px;transition:all .2s}
        .dataTables_wrapper .dataTables_filter input:focus{border-color:#3b82f6;box-shadow:0 0 0 2px rgba(59,130,246,.15);background-color:#fff;width:260px}
        table.dataTable thead th{background:#f8fafc!important;color:#475569!important;font-weight:600!important;font-size:.6875rem!important;text-transform:uppercase!important;letter-spacing:.05em!important;padding:.6rem .75rem!important;border-bottom:1px solid #e2e8f0!important;white-space:nowrap!important}
        table.dataTable tbody td{padding:.55rem .75rem!important;font-size:.8125rem!important;color:#334155!important;border-bottom:1px solid #f1f5f9!important;vertical-align:middle!important}
        table.dataTable tbody tr{transition:background .1s}
        table.dataTable tbody tr:hover{background:#f0f9ff!important}
        table.dataTable tbody tr:last-child td{border-bottom:none!important}
        table.dataTable thead .sorting:before,table.dataTable thead .sorting:after,table.dataTable thead .sorting_asc:before,table.dataTable thead .sorting_asc:after,table.dataTable thead .sorting_desc:before,table.dataTable thead .sorting_desc:after{font-size:.55rem!important;opacity:.2;bottom:auto!important;top:50%!important;transform:translateY(-50%)}
        table.dataTable thead .sorting_asc:before,table.dataTable thead .sorting_desc:after{opacity:.7;color:#3b82f6}
        .dataTables_wrapper .dataTables_info{font-size:.7rem;color:#94a3b8;padding-top:.75rem}
        .dataTables_wrapper .dataTables_paginate{padding-top:.75rem}
        .dataTables_wrapper .dataTables_paginate .paginate_button{border:none!important;border-radius:.25rem!important;padding:.3rem .55rem!important;margin:0 1px!important;font-size:.7rem!important;font-weight:500!important;background:transparent!important;color:#64748b!important;transition:all .15s!important;min-width:1.75rem;text-align:center}
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current){background:#f1f5f9!important;color:#1e40af!important}
        .dataTables_wrapper .dataTables_paginate .paginate_button.current{background:#2563eb!important;color:#fff!important;font-weight:600!important;box-shadow:0 1px 2px rgba(37,99,235,.3)}
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled{opacity:.3!important;cursor:default!important}
        .dataTables_empty{padding:2rem!important;color:#94a3b8!important;font-size:.85rem!important}
        .dataTables_processing{background:rgba(255,255,255,.9)!important;border:none!important;font-size:.8rem;color:#64748b}
        /* ── Modal ── */
        .modal-overlay{background:rgba(15,23,42,.5);backdrop-filter:blur(4px)}
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        @include('partials.sidebar')
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-gray-200 h-14 flex items-center justify-between px-6 flex-shrink-0 gap-4">
                {{-- ─── Phase 7D: Global Search ──────────────────────────
                     Alpine-powered top-nav search. Hits /search (max 5 hits per
                     entity type) and renders grouped results in a dropdown.
                     Cmd/Ctrl+K from anywhere in the app focuses this input. --}}
                <div
                    class="flex-1 max-w-xl relative"
                    x-data="globalSearch()"
                    @keydown.window.prevent.meta.k="focusSearch()"
                    @keydown.window.prevent.ctrl.k="focusSearch()"
                >
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            x-ref="searchInput"
                            x-model="query"
                            @input.debounce.250ms="runSearch()"
                            @focus="open = true"
                            @keydown.escape="closeAndBlur()"
                            @keydown.arrow-down.prevent="moveCursor(1)"
                            @keydown.arrow-up.prevent="moveCursor(-1)"
                            @keydown.enter.prevent="openHighlighted()"
                            placeholder="Search projects, employees, POs, RFIs, invoices…"
                            class="w-full pl-9 pr-12 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
                        >
                        <kbd class="absolute right-3 top-1/2 -translate-y-1/2 hidden md:inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-mono text-gray-400 bg-white border border-gray-200 rounded pointer-events-none">
                            <span x-text="isMac ? '⌘' : 'Ctrl'"></span>K
                        </kbd>
                    </div>

                    {{-- Dropdown --}}
                    <div
                        x-show="open && query.length >= 2"
                        x-transition.opacity.duration.100ms
                        @click.away="open = false"
                        class="absolute left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-xl max-h-[70vh] overflow-y-auto z-50"
                        style="display:none;"
                    >
                        {{-- Loading --}}
                        <template x-if="loading">
                            <div class="px-4 py-6 text-center text-sm text-gray-400">
                                <svg class="inline-block animate-spin w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4zm2 5.3A7.96 7.96 0 014 12H0c0 3 1.1 5.8 3 7.9l3-2.6z"></path></svg>
                                Searching…
                            </div>
                        </template>

                        {{-- Empty --}}
                        <template x-if="!loading && results.count === 0">
                            <div class="px-4 py-6 text-center">
                                <p class="text-sm text-gray-500">No matches for <span class="font-mono text-gray-700" x-text="'&quot;' + query + '&quot;'"></span></p>
                                <p class="text-xs text-gray-400 mt-1">Try a project number, person's name, or invoice number.</p>
                            </div>
                        </template>

                        {{-- Results --}}
                        <template x-if="!loading && results.count > 0">
                            <div class="py-1">
                                <template x-for="(group, gi) in results.groups" :key="gi">
                                    <div>
                                        <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-100 sticky top-0">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500" x-text="group.label"></span>
                                            <span class="text-[10px] text-gray-400 ml-1" x-text="'(' + group.items.length + ')'"></span>
                                        </div>
                                        <template x-for="(item, ii) in group.items" :key="ii">
                                            <a
                                                :href="item.url"
                                                @mouseenter="setCursor(gi, ii)"
                                                :class="isHighlighted(gi, ii) ? 'bg-blue-50' : ''"
                                                class="block px-3 py-2 hover:bg-blue-50 transition border-b border-gray-50 last:border-0"
                                            >
                                                <div class="text-sm font-medium text-gray-900" x-text="item.title"></div>
                                                <div class="text-xs text-gray-500 mt-0.5" x-text="item.subtitle"></div>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="px-3 py-1.5 bg-gray-50 border-t border-gray-100 text-[10px] text-gray-400 flex items-center justify-between">
                            <span>↑↓ navigate · Enter open · Esc close</span>
                            <span x-text="results.count + ' result' + (results.count === 1 ? '' : 's')"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <span class="text-xs text-gray-400 hidden lg:inline">{{ now()->format('D, M j, Y') }}</span>
                    <div class="h-5 w-px bg-gray-200 hidden lg:block"></div>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 hover:opacity-80 transition focus:outline-none">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-xs font-bold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                            <div class="text-left">
                                <span class="text-sm font-medium text-gray-700 block leading-tight">{{ Auth::user()->name }}</span>
                                <span class="text-[10px] text-gray-400 leading-tight">{{ ucfirst(str_replace('_', ' ', Auth::user()->role ?? 'User')) }}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50" style="display: none;">
                            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                My Profile
                            </a>
                            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Settings
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <button type="submit" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition w-full text-left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-auto p-6">@yield('content')</main>
        </div>
    </div>

    <script>
    // ─── Global Search Alpine component (Phase 7D) ─────────────────
    // Lives at the top so it's defined before Alpine evaluates templates.
    function globalSearch() {
        return {
            query: '',
            open: false,
            loading: false,
            results: { count: 0, groups: [] },
            cursorGroup: 0,
            cursorItem: 0,
            isMac: navigator.platform.toUpperCase().includes('MAC'),

            focusSearch() {
                this.$refs.searchInput.focus();
                this.$refs.searchInput.select();
            },

            closeAndBlur() {
                this.open = false;
                this.$refs.searchInput.blur();
            },

            async runSearch() {
                if (this.query.trim().length < 2) {
                    this.results = { count: 0, groups: [] };
                    return;
                }
                this.loading = true;
                this.open = true;
                try {
                    const url = window.BASE_URL + '/search?q=' + encodeURIComponent(this.query);
                    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    this.results = { count: data.count || 0, groups: data.groups || [] };
                    this.cursorGroup = 0;
                    this.cursorItem = 0;
                } catch (e) {
                    this.results = { count: 0, groups: [] };
                } finally {
                    this.loading = false;
                }
            },

            // Keyboard nav across grouped results (skip group headers).
            moveCursor(delta) {
                if (this.results.count === 0) return;
                let g = this.cursorGroup, i = this.cursorItem;
                if (delta > 0) {
                    i++;
                    if (i >= this.results.groups[g].items.length) { g++; i = 0; }
                    if (g >= this.results.groups.length) { g = 0; i = 0; }
                } else {
                    i--;
                    if (i < 0) {
                        g--;
                        if (g < 0) g = this.results.groups.length - 1;
                        i = this.results.groups[g].items.length - 1;
                    }
                }
                this.cursorGroup = g;
                this.cursorItem = i;
            },

            setCursor(g, i) { this.cursorGroup = g; this.cursorItem = i; },
            isHighlighted(g, i) { return this.cursorGroup === g && this.cursorItem === i; },

            openHighlighted() {
                if (this.results.count === 0) return;
                const item = this.results.groups[this.cursorGroup]?.items?.[this.cursorItem];
                if (item) window.location.href = item.url;
            },
        };
    }

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    const Toast = Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:3000, timerProgressBar:true,
        didOpen:(t)=>{t.onmouseenter=Swal.stopTimer;t.onmouseleave=Swal.resumeTimer}});

    @if(Session::has('success')) Toast.fire({icon:'success',title:@json(Session::get('success'))}); @endif
    @if(Session::has('error')) Toast.fire({icon:'error',title:@json(Session::get('error'))}); @endif
    @if(Session::has('warning')) Toast.fire({icon:'warning',title:@json(Session::get('warning'))}); @endif

    function confirmDelete(url, table, redirectAfterDelete) {
        Swal.fire({ title:'Are you sure?', text:"This cannot be undone!", icon:'warning',
            showCancelButton:true, confirmButtonColor:'#dc2626', cancelButtonColor:'#6b7280',
            confirmButtonText:'Yes, delete!', cancelButtonText:'Cancel', reverseButtons:true
        }).then((r)=>{
            if(r.isConfirmed){
                $.ajax({ url:url, type:'DELETE',
                    success:function(res){
                        Toast.fire({icon:'success',title:res.message||'Deleted!'});
                        if (table && table.ajax) {
                            table.ajax.reload(null,false);
                        } else if (redirectAfterDelete) {
                            window.location.href = redirectAfterDelete;
                        }
                    },
                    error:function(xhr){ Toast.fire({icon:'error',title:xhr.responseJSON?.message||'Error deleting'}); }
                });
            }
        });
    }

    function openModal(id){ document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id){ document.getElementById(id).classList.add('hidden'); }

    function submitForm(formId, url, method, table, modalId) {
        let form = document.getElementById(formId);
        let formData = new FormData(form);
        let data = {};
        formData.forEach((v,k) => data[k] = v);

        $.ajax({
            url: url, type: method, data: data,
            success: function(res) {
                const message = res.message || 'Saved!';
                closeModal(modalId);
                if (table && table.ajax) {
                    // DataTables flow: ajax-reload the table, show the toast inline.
                    Toast.fire({icon:'success', title:message});
                    table.ajax.reload(null, false);
                    form.reset();
                } else if (!table) {
                    // Full-page reload flow (e.g. dashboard quick-add modals): the
                    // toast won't survive the reload because Toast.fire renders
                    // into the current DOM. Stash it in localStorage so the layout
                    // shows it on the next page load.
                    try {
                        localStorage.setItem('flash_toast', JSON.stringify({icon:'success', title:message}));
                    } catch (e) { /* private mode / quota — fall through, toast is lost but save succeeded */ }
                    window.location.reload();
                    return;
                } else {
                    Toast.fire({icon:'success', title:message});
                    form.reset();
                }
            },
            error: function(xhr) {
                let errors = xhr.responseJSON?.errors;
                if(errors) {
                    let msg = Object.values(errors).flat().join('<br>');
                    Swal.fire({icon:'error', title:'Validation Error', html:msg, confirmButtonColor:'#2563eb'});
                } else {
                    Toast.fire({icon:'error', title:xhr.responseJSON?.message||'Error saving'});
                }
            }
        });
    }

    // Read any flash-toast that survived a page reload (set by submitForm
    // before window.location.reload(), since the prior Toast.fire would be
    // discarded along with the page).
    (function () {
        try {
            const stash = localStorage.getItem('flash_toast');
            if (stash) {
                localStorage.removeItem('flash_toast');
                const payload = JSON.parse(stash);
                Toast.fire(payload);
            }
        } catch (e) { /* ignore */ }
    })();

    function loadEdit(url, formId, modalId, fields) {
        $.get(url, function(data) {
            let form = document.getElementById(formId);
            fields.forEach(function(f) {
                let el = form.querySelector('[name="'+f+'"]');
                if(el) el.value = data[f] ?? '';
            });
            openModal(modalId);
        });
    }

    $.extend($.fn.dataTable.defaults, {
        processing: true, serverSide: true, pageLength: 15,
        lengthMenu: [[10,15,25,50],[10,15,25,50]],
        language: { search:"", searchPlaceholder:"Search...", lengthMenu:"Show _MENU_",
            info:"_START_–_END_ of _TOTAL_", infoEmpty:"No entries", infoFiltered:"(from _MAX_)",
            zeroRecords:"No records found", processing:"Loading...",
            paginate:{first:"«",last:"»",next:"›",previous:"‹"} },
        dom:'<"flex items-center justify-between flex-wrap gap-3 mb-2"lf>rt<"flex items-center justify-between flex-wrap gap-3 border-t border-gray-100 pt-2"ip>'
    });
    </script>
    @stack('scripts')
</body>
</html>
