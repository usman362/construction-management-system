<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CMS') - BuildTrack</title>
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
            <header class="bg-white border-b border-gray-200 h-14 flex items-center justify-end px-6 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <span class="text-xs text-gray-400">{{ now()->format('D, M j, Y') }}</span>
                    <div class="h-5 w-px bg-gray-200"></div>
                    <a href="{{ route('profile') }}" class="flex items-center gap-2 hover:opacity-80 transition">
                        <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center">
                            <span class="text-white text-xs font-bold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700 block leading-tight">{{ Auth::user()->name }}</span>
                            <span class="text-[10px] text-gray-400 leading-tight">{{ Auth::user()->role_label }}</span>
                        </div>
                    </a>
                    <a href="{{ route('profile') }}" class="text-xs text-gray-400 hover:text-blue-500 transition flex items-center gap-1" title="Profile Settings">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="text-xs text-gray-400 hover:text-red-500 transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout
                        </button>
                    </form>
                </div>
            </header>
            <main class="flex-1 overflow-auto p-6">@yield('content')</main>
        </div>
    </div>

    <script>
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
                Toast.fire({icon:'success', title:res.message||'Saved!'});
                closeModal(modalId);
                if (table && table.ajax) {
                    table.ajax.reload(null, false);
                } else if (!table) {
                    window.location.reload();
                    return;
                }
                form.reset();
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
