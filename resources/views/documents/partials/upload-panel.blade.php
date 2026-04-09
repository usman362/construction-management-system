{{--
    Reusable document upload panel.
    Include with: @include('documents.partials.upload-panel', [
        'documentableType' => get_class($project),   // e.g. App\Models\Project
        'documentableId'   => $project->id,
        'documents'        => $project->documents,     // Collection
        'categories'       => ['proposal','photo','contract','other'], // optional filter
    ])
--}}

@php
    $uid = 'doc_' . Str::random(6);
    $allCategories = [
        'proposal' => 'Proposal', 'photo' => 'Photo', 'change_order' => 'Change Order',
        'purchase_order' => 'Purchase Order', 'delivery_ticket' => 'Delivery Ticket',
        'estimate' => 'Estimate', 'daily_log' => 'Daily Log', 'report' => 'Report',
        'correspondence' => 'Correspondence', 'contract' => 'Contract',
        'permit' => 'Permit', 'insurance' => 'Insurance', 'other' => 'Other',
    ];
    $availableCategories = isset($categories) ? array_intersect_key($allCategories, array_flip($categories)) : $allCategories;
    $docs = ($documents ?? collect())->sortByDesc('created_at');
@endphp

<div class="bg-white rounded-lg shadow-md p-6" id="{{ $uid }}_panel">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Documents</h2>
        <button type="button" onclick="openModal('{{ $uid }}_modal')" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Upload
        </button>
    </div>

    @if($docs->isEmpty())
        <p class="text-sm text-gray-400 py-6 text-center">No documents uploaded yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Title</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Category</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">File</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Size</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Uploaded</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600" width="80">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($docs as $doc)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $doc->title }}</td>
                            <td class="px-3 py-2"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ ucwords(str_replace('_', ' ', $doc->category)) }}</span></td>
                            <td class="px-3 py-2 text-gray-500 truncate max-w-[200px]" title="{{ $doc->file_name }}">{{ $doc->file_name }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $doc->file_size_formatted }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $doc->created_at->format('M j, Y') }}</td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('documents.download', $doc) }}" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-blue-600 hover:bg-blue-50" title="Download">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    </a>
                                    <button type="button" onclick="confirmDelete('{{ route('documents.destroy', $doc) }}')" class="w-7 h-7 inline-flex items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Upload Modal -->
<div id="{{ $uid }}_modal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay" onclick="if(event.target===this)closeModal('{{ $uid }}_modal')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Upload Document</h3>
            <button type="button" onclick="closeModal('{{ $uid }}_modal')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="{{ $uid }}_form" class="p-6 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="documentable_type" value="{{ $documentableType }}">
            <input type="hidden" name="documentable_id" value="{{ $documentableId }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                <select name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    @foreach($availableCategories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File * <span class="text-xs text-gray-400">(max 50MB)</span></label>
                <input type="file" name="file" required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </form>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" onclick="closeModal('{{ $uid }}_modal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button type="button" onclick="uploadDocument('{{ $uid }}_form', '{{ $uid }}_modal')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Upload</button>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
function uploadDocument(formId, modalId) {
    var form = document.getElementById(formId);
    var formData = new FormData(form);

    $.ajax({
        url: '{{ route("documents.store") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(res) {
            closeModal(modalId);
            Swal.fire({ icon: 'success', title: 'Uploaded', text: res.message, timer: 2000, showConfirmButton: false });
            setTimeout(function() { location.reload(); }, 1500);
        },
        error: function(xhr) {
            var msg = xhr.responseJSON?.message || 'Upload failed.';
            if (xhr.responseJSON?.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        }
    });
}
</script>
@endPushOnce
