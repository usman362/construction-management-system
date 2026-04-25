{{-- Base email layout reused by every Notification mail in the app.
     Tailwind classes don't work in most mail clients, so this uses
     inline styles. Keep it intentionally simple — Outlook is harsh. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'BuildTrack Notification' }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#111827;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(90deg,#2563eb,#1d4ed8); padding:20px 28px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:700; letter-spacing:-0.01em;">
                                BuildTrack
                            </h1>
                            <p style="margin:4px 0 0; color:#dbeafe; font-size:13px;">Construction Management</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px;">
                            @if(!empty($greeting))
                                <p style="margin:0 0 16px; font-size:15px; color:#111827;">{{ $greeting }}</p>
                            @endif

                            @if(!empty($intro))
                                <p style="margin:0 0 16px; font-size:15px; line-height:1.55; color:#374151;">
                                    {!! $intro !!}
                                </p>
                            @endif

                            {{-- Detail card — used by most notifications --}}
                            @if(!empty($details) && is_array($details))
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; margin:16px 0;">
                                    <tr>
                                        <td style="padding:16px 20px;">
                                            @foreach($details as $label => $value)
                                                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                                                    <span style="color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; font-size:11px; font-weight:600;">{{ $label }}</span><br>
                                                    <span style="color:#111827; font-size:14px;">{!! $value !!}</span>
                                                </p>
                                            @endforeach
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if(!empty($body))
                                <div style="font-size:15px; line-height:1.55; color:#374151; margin:16px 0;">
                                    {!! $body !!}
                                </div>
                            @endif

                            {{-- Action button --}}
                            @if(!empty($actionUrl))
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 8px;">
                                    <tr>
                                        <td>
                                            <a href="{{ $actionUrl }}" style="display:inline-block; padding:12px 24px; background:#2563eb; color:#ffffff; text-decoration:none; font-weight:600; border-radius:6px; font-size:14px;">
                                                {{ $actionText ?? 'View in BuildTrack' }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if(!empty($footer))
                                <p style="margin:24px 0 0; padding-top:16px; border-top:1px solid #e5e7eb; font-size:12px; color:#9ca3af; line-height:1.5;">
                                    {!! $footer !!}
                                </p>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f9fafb; padding:16px 28px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                You received this because you are part of the BuildTrack system.<br>
                                Sent {{ now()->format('M j, Y \a\t g:i A') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
