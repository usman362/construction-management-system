{{--
    Reusable signature-pad partial.

    Usage:
        @include('partials.signature-pad', [
            'document'    => $changeOrder,           // model with signature, signature_name, signed_at attrs
            'submitUrl'   => route('projects.change-orders.sign', [$project, $changeOrder]),
            'docLabel'    => 'Change Order #' . $changeOrder->co_number,   // shown in legal text
        ])

    The model just needs `signature`, `signature_name`, `signed_at` columns.
    Rendered as a self-contained Alpine component so it works on any show
    page without conflicts. No external library — uses native canvas APIs
    so it works on Apple Pencil / stylus / mouse / touchscreen identically.
--}}

@php
    $domId      = 'sig-' . uniqid();
    $signed     = !empty($document->signature);
    $docLabel   = $docLabel ?? 'this document';
@endphp

<div
    id="{{ $domId }}"
    x-data="signaturePad('{{ $domId }}', @js($submitUrl), {{ $signed ? 'true' : 'false' }})"
    class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm"
>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
            Signature
        </h3>
        @if($signed)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                Signed
            </span>
        @endif
    </div>

    {{-- ── Already signed: show the signature, name, and timestamp ── --}}
    <template x-if="signed">
        <div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-3">
                <img src="{{ $signed ? $document->signature : '' }}" alt="Signature" class="max-h-32 mx-auto">
            </div>
            <div class="text-sm text-gray-700">
                <div><span class="text-gray-500">Signed by:</span> <span class="font-semibold">{{ $document->signature_name ?? '—' }}</span></div>
                <div class="text-xs text-gray-500 mt-0.5">
                    {{ optional($document->signed_at)->format('M j, Y g:i A') ?? '—' }}
                    @if($document->signed_by && $document->signedBy ?? null)
                        · captured by {{ $document->signedBy->name }}
                    @endif
                </div>
            </div>
            <button type="button" @click="clearAndUnsign()" class="mt-3 text-xs text-red-600 hover:text-red-800 underline">
                Clear and re-sign
            </button>
        </div>
    </template>

    {{-- ── Not yet signed: show the canvas + form ── --}}
    <template x-if="!signed">
        <div>
            <p class="text-xs text-gray-500 mb-2">
                Sign below to record approval of <strong>{{ $docLabel }}</strong>.
                Your signature, typed name, and timestamp are saved as a permanent record.
            </p>

            <div class="border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 relative">
                <canvas
                    x-ref="canvas"
                    width="600"
                    height="200"
                    class="w-full h-48 cursor-crosshair touch-none rounded-lg"
                    @pointerdown.prevent="startStroke($event)"
                    @pointermove.prevent="continueStroke($event)"
                    @pointerup.prevent="endStroke($event)"
                    @pointerleave.prevent="endStroke($event)"
                ></canvas>
                <div x-show="empty" class="absolute inset-0 flex items-center justify-center pointer-events-none text-gray-300 text-sm" style="display:none;">
                    Sign here
                </div>
            </div>

            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type your name *</label>
                    <input type="text" x-model="signerName" placeholder="John Smith"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div class="flex items-end gap-2">
                    <button type="button" @click="clearCanvas()" class="bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg border border-gray-300 transition">
                        Clear
                    </button>
                    <button type="button" @click="save()" :disabled="empty || !signerName || saving"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition">
                        <span x-show="!saving">Save Signature</span>
                        <span x-show="saving">Saving…</span>
                    </button>
                </div>
            </div>

            <p class="mt-2 text-[11px] text-gray-400 italic">
                By signing, you confirm review and approval of this document.
            </p>
        </div>
    </template>

    <div x-show="message" x-text="message" :class="messageClass" class="mt-3 rounded-lg p-2 text-xs"></div>
</div>

{{-- The component definition only needs to be defined once per page load. --}}
@once
@push('scripts')
<script>
function signaturePad(domId, submitUrl, initiallySigned) {
    return {
        signed: initiallySigned,
        empty: true,
        saving: false,
        signerName: '',
        message: '',
        messageClass: '',
        ctx: null,
        drawing: false,
        last: null,

        init() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            // Match the canvas's internal pixel size to its CSS size for crisp
            // strokes on retina displays.
            const ratio = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();
            canvas.width  = rect.width  * ratio;
            canvas.height = rect.height * ratio;
            this.ctx = canvas.getContext('2d');
            this.ctx.scale(ratio, ratio);
            this.ctx.strokeStyle = '#0f172a';
            this.ctx.lineWidth   = 2;
            this.ctx.lineCap     = 'round';
            this.ctx.lineJoin    = 'round';
        },

        startStroke(e) {
            if (!this.ctx) return;
            this.drawing = true;
            this.empty = false;
            const p = this.point(e);
            this.last = p;
            this.ctx.beginPath();
            this.ctx.moveTo(p.x, p.y);
        },

        continueStroke(e) {
            if (!this.drawing || !this.ctx) return;
            const p = this.point(e);
            this.ctx.lineTo(p.x, p.y);
            this.ctx.stroke();
            this.last = p;
        },

        endStroke() {
            this.drawing = false;
        },

        point(e) {
            const rect = this.$refs.canvas.getBoundingClientRect();
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        },

        clearCanvas() {
            if (!this.ctx) return;
            const c = this.$refs.canvas;
            this.ctx.clearRect(0, 0, c.width, c.height);
            this.empty = true;
            this.message = '';
        },

        clearAndUnsign() {
            // Don't actually delete the saved signature — let the server handle
            // that. Simply switch the local UI back to "not signed" so the user
            // can sign again. The next save() POST overwrites the old row.
            this.signed = false;
            this.signerName = '';
            // Wait for x-template to render the canvas
            this.$nextTick(() => this.init());
        },

        async save() {
            if (this.empty || !this.signerName.trim() || !this.ctx) return;
            this.saving = true;
            this.message = '';

            const dataUrl = this.$refs.canvas.toDataURL('image/png');

            try {
                const res = await fetch(submitUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        signature: dataUrl,
                        signature_name: this.signerName.trim(),
                    }),
                });
                const body = await res.json();
                if (!res.ok || body.success === false) {
                    throw new Error(body.message || 'Failed to save signature');
                }
                this.message = body.message || 'Signature saved.';
                this.messageClass = 'bg-green-50 border border-green-200 text-green-800';
                // Reload so the page reflects the saved state with timestamp/name from the server.
                setTimeout(() => location.reload(), 600);
            } catch (e) {
                this.message = e.message;
                this.messageClass = 'bg-red-50 border border-red-200 text-red-800';
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
@endonce
