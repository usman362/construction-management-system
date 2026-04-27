<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QR Sticker — {{ $equipment->name }}</title>
    {{--
        QR sticker print page — generates the QR client-side via QRCode.js so
        no server-side PHP QR library is needed (works on any shared cPanel
        host out of the box). Prints a single 3" × 3" sticker by default; the
        user can stick the printout on the equipment.
    --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; padding: 20px; background: #f3f4f6; }
        .sticker {
            width: 3in; height: 3in;
            background: white;
            border: 2px solid #1e3a8a;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header { text-align: center; margin-bottom: 8px; }
        .company { font-size: 10pt; font-weight: bold; color: #1e3a8a; }
        .equip-name { font-size: 12pt; font-weight: bold; color: #111; margin-top: 2px; line-height: 1.1; }
        .qr-canvas { margin: 8px 0; }
        .footer { text-align: center; margin-top: 4px; }
        .footer-label { font-size: 7pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .footer-sn { font-size: 8pt; color: #374151; font-family: monospace; }
        .actions { text-align: center; margin: 24px 0; }
        .actions button { background: #2563eb; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .actions button:hover { background: #1d4ed8; }
        @media print {
            body { background: white; padding: 0; }
            .sticker { box-shadow: none; border: 1.5px solid #000; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sticker">
        <div class="header">
            <div class="company">{{ \App\Models\Setting::get('company_name', 'BuildTrack') }}</div>
            <div class="equip-name">{{ $equipment->name }}</div>
        </div>
        <div id="qr" class="qr-canvas"></div>
        <div class="footer">
            <div class="footer-label">Scan to check in/out</div>
            @if($equipment->serial_number)
                <div class="footer-sn">SN: {{ $equipment->serial_number }}</div>
            @endif
        </div>
    </div>

    <div class="actions">
        <button onclick="window.print()">🖨 Print Sticker</button>
        <button onclick="window.close()" style="background:#6b7280; margin-left:8px;">Close</button>
    </div>

    <script>
        const url = @json($scanUrl);
        // Type 4 (33×33), error correction H — robust for printed stickers
        const qr = qrcode(0, 'H');
        qr.addData(url);
        qr.make();
        document.getElementById('qr').innerHTML = qr.createImgTag(6, 0);
    </script>
</body>
</html>
