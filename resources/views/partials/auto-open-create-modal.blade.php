{{--
    Reusable: auto-open the "Add" modal on a list page when the URL has ?new=1.

    The dashboard's "New X" quick actions and the deprecated /xxx/create
    redirects all land on `/xxx?new=1`. This snippet pops the modal as soon
    as the page is ready, then strips the query string so a refresh doesn't
    keep re-opening it.

    Each list page already has an `openCreateModal()` JS function defined for
    the "+ Add" button — this just calls it.

    NOTE: This file is JS-body-only (no <script> wrapper) so it can be
    @include()d inside an existing <script> block in the host page.

    Usage (inside an existing @push('scripts') <script>...</script> @endpush):
        @include('partials.auto-open-create-modal')
--}}

document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('new') === '1' && typeof openCreateModal === 'function') {
        openCreateModal();
        params.delete('new');
        const cleanQuery = params.toString();
        history.replaceState(null, '', window.location.pathname + (cleanQuery ? '?' + cleanQuery : ''));
    }
});
