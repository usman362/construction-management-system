<?php

namespace App\Notifications;

use App\Models\EmployeeCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 2026-05-12 (Brenda — Phase 1 / Cert Expiry Alerts).
 *
 * Per-employee email when one of their own certifications crosses a
 * notification milestone (60 / 30 / 7 days from expiry, or already
 * expired). Sent via the scheduled `certs:notify-expiring` command —
 * which also tracks last-sent-per-milestone on the cert row so we
 * don't email the same person about the same cert every run.
 *
 * Bundled as one cert per email (employees usually only have one or
 * two active certs, and the focused subject "[Cert] expires in N
 * days" actually gets read). The admin/HR digest stays separate.
 */
class CertificationExpiringEmployee extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmployeeCertification $certification,
        public string $milestone,   // '60', '30', '7', or 'expired'
        public int $daysUntilExpiry // negative if already expired
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cert    = $this->certification;
        $emp     = $cert->employee;
        $expiry  = optional($cert->expiry_date)->format('M j, Y') ?? '—';
        $name    = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) ?: 'team member';

        // Subject line tailored to milestone — keeps the inbox preview useful.
        $subject = match ($this->milestone) {
            'expired' => "{$cert->name} has expired",
            '7'       => "{$cert->name} expires in 7 days",
            '30'      => "{$cert->name} expires in 30 days",
            '60'      => "{$cert->name} expires in about 2 months",
            default   => "{$cert->name} expiry reminder",
        };

        $tone = match ($this->milestone) {
            'expired' => '🔴 Your <strong>' . e($cert->name) . '</strong> certification has expired (expiry date was ' . $expiry . '). Please renew it as soon as possible so your work assignments aren\'t affected.',
            '7'       => '🟠 Your <strong>' . e($cert->name) . '</strong> certification expires <strong>in ' . $this->daysUntilExpiry . ' day(s)</strong> (on ' . $expiry . '). Please get the renewal scheduled this week.',
            '30'      => '🟡 Your <strong>' . e($cert->name) . '</strong> certification expires <strong>in ' . $this->daysUntilExpiry . ' days</strong> (on ' . $expiry . '). A good time to start the renewal process.',
            '60'      => '🔵 A heads-up — your <strong>' . e($cert->name) . '</strong> certification expires on ' . $expiry . ' (about ' . $this->daysUntilExpiry . ' days from now). Plenty of time to renew without rushing.',
            default   => 'Your <strong>' . e($cert->name) . '</strong> certification needs attention.',
        };

        $body = '<p style="margin:0 0 16px; font-size:14px; color:#374151;">' . $tone . '</p>';

        if ($cert->certification_number) {
            $body .= '<p style="margin:0 0 8px; font-size:13px; color:#6b7280;">Certification #: <code style="font-family:monospace;">' . e($cert->certification_number) . '</code></p>';
        }
        if ($cert->issuing_authority) {
            $body .= '<p style="margin:0 0 8px; font-size:13px; color:#6b7280;">Issuing authority: ' . e($cert->issuing_authority) . '</p>';
        }
        $body .= '<p style="margin:16px 0 0; font-size:13px; color:#9ca3af;">If you\'ve already renewed it, send the updated certificate to your manager and they\'ll update the record.</p>';

        return (new MailMessage())
            ->subject($subject)
            ->view('emails.layout', [
                'subject'    => $subject,
                'greeting'   => 'Hi ' . explode(' ', $name)[0] . ',',
                'intro'      => '',
                'body'       => $body,
                'actionUrl'  => null,
                'actionText' => null,
                'footer'     => 'You\'re getting this because the cert is logged in our system. Reply to this email if anything looks wrong.',
            ]);
    }
}
