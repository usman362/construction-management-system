<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Daily summary of certifications expiring in the next 90 days (and any
 * already expired). Sent by the scheduled `certs:notify-expiring` command
 * to admins and accountants every weekday morning.
 *
 * Bundled (one email per recipient instead of one per cert) so HR doesn't
 * drown — they see a single digest grouped by urgency bucket.
 */
class CertificationExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Collection $expired   Certifications past expiry_date
     * @param Collection $in30      Expiring within 30 days
     * @param Collection $in60      Expiring 31-60 days out
     * @param Collection $in90      Expiring 61-90 days out
     */
    public function __construct(
        public Collection $expired,
        public Collection $in30,
        public Collection $in60,
        public Collection $in90,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/dashboard#certifications-watch');

        $sections = [];
        if ($this->expired->count())  { $sections[] = $this->renderSection('🔴 Already expired', $this->expired); }
        if ($this->in30->count())     { $sections[] = $this->renderSection('🟠 Expiring within 30 days', $this->in30); }
        if ($this->in60->count())     { $sections[] = $this->renderSection('🟡 Expiring in 31-60 days', $this->in60); }
        if ($this->in90->count())     { $sections[] = $this->renderSection('🔵 Expiring in 61-90 days', $this->in90); }

        $body = empty($sections)
            ? '<p style="margin:0; color:#10b981;">All certifications are current — nothing expires in the next 90 days. 🎉</p>'
            : implode('', $sections);

        $totalCount = $this->expired->count() + $this->in30->count() + $this->in60->count() + $this->in90->count();

        return (new MailMessage())
            ->subject('Certification expiry watch — ' . $totalCount . ' need attention')
            ->view('emails.layout', [
                'subject'    => 'Certification expiry watch',
                'greeting'   => 'Hi ' . ($notifiable->name ?? '') . ',',
                'intro'      => 'Here is your morning summary of certifications that need attention. Renew the ones below before they expire to keep your team compliant.',
                'body'       => $body,
                'actionUrl'  => $url,
                'actionText' => 'Open Certifications Watch',
                'footer'     => 'You receive this because you have admin or accountant access. To stop these emails, ask your administrator.',
            ]);
    }

    /**
     * Render one urgency bucket as a labelled list.
     */
    private function renderSection(string $title, Collection $certs): string
    {
        $rows = $certs->map(function ($c) {
            $name = trim(($c->employee->first_name ?? '') . ' ' . ($c->employee->last_name ?? '')) ?: '—';
            $expiry = optional($c->expiry_date)->format('M j, Y') ?? '—';
            return '<li style="margin:6px 0; font-size:14px; color:#374151;">'
                . '<strong>' . e($name) . '</strong> — '
                . e($c->name ?? 'Cert') . ' '
                . '<span style="color:#9ca3af;">(' . $expiry . ')</span>'
                . '</li>';
        })->implode('');

        return '<div style="margin:16px 0;">'
            . '<h3 style="margin:0 0 8px; font-size:14px; color:#111827;">' . e($title) . ' (' . $certs->count() . ')</h3>'
            . '<ul style="margin:0; padding-left:20px;">' . $rows . '</ul>'
            . '</div>';
    }
}
