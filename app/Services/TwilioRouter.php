<?php

namespace App\Services;

use App\Models\DailyLog;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\TimeClockEntry;
use App\Models\TwilioMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 2026-05-12 (Brenda — Phase 5 / WhatsApp + SMS bot).
 *
 * Inbound Twilio webhook router. Given a raw webhook payload, this
 * class:
 *
 *  1) Audits the message into the `twilio_messages` table.
 *  2) Resolves the sender's phone → Employee (matching on last-10-digits
 *     across personal_cell / work_cell / phone).
 *  3) Classifies the intent based on body text + media:
 *       - Image  → likely a paper timesheet (Snap-a-Timesheet pipeline).
 *       - Audio  → voice note → Groq Whisper → AI Daily Log Generator.
 *       - Text   → command (clock in / clock out / status / help).
 *  4) Dispatches to the right handler and returns a string reply.
 *
 * The caller (TwilioWebhookController) wraps that string in TwiML XML so
 * Twilio delivers it back to the foreman.
 *
 * Conservative defaults: anything ambiguous gets a polite "I'm not sure
 * what you meant" reply instead of doing the wrong thing. The audit row
 * captures everything so a human can pick up the slack later.
 */
class TwilioRouter
{
    public function __construct(
        private TimesheetOcrService $timesheetOcr,
        private DailyLogAiService $dailyLogAi,
    ) {}

    public function handle(Request $request): string
    {
        $payload = $request->all();
        $from    = (string) ($payload['From'] ?? '');
        $to      = (string) ($payload['To']   ?? '');
        $body    = trim((string) ($payload['Body'] ?? ''));
        $numMedia = (int) ($payload['NumMedia'] ?? 0);
        $channel  = str_starts_with($from, 'whatsapp:') ? 'whatsapp' : 'sms';

        $media = [];
        for ($i = 0; $i < $numMedia; $i++) {
            $media[] = [
                'url'          => (string) ($payload['MediaUrl' . $i] ?? ''),
                'content_type' => (string) ($payload['MediaContentType' . $i] ?? ''),
            ];
        }

        // Audit + resolve employee
        $employee = $this->resolveEmployee($from);
        $message  = TwilioMessage::create([
            'message_sid' => $payload['MessageSid'] ?? null,
            'from_phone'  => $from,
            'to_phone'    => $to,
            'channel'     => $channel,
            'body'        => $body,
            'num_media'   => $numMedia,
            'media'       => $media,
            'employee_id' => $employee?->id,
            'raw_payload' => $payload,
            'status'      => 'received',
        ]);

        // Unknown number → polite refusal
        if (! $employee) {
            $reply = $this->replyUnknownNumber();
            $message->update(['intent' => 'unknown', 'status' => 'failed', 'reply' => $reply, 'error' => 'phone not registered']);
            return $reply;
        }

        try {
            // ─── Media routing ────────────────────────────────────
            if ($numMedia > 0) {
                $first = $media[0];
                $type = $first['content_type'] ?? '';
                if (str_starts_with($type, 'image/')) {
                    return $this->handleImage($message, $employee, $first);
                }
                if (str_starts_with($type, 'audio/') || $type === 'video/mp4' /* iOS sometimes sends m4a as video/mp4 */) {
                    return $this->handleAudio($message, $employee, $first);
                }
                // Unknown media — fall through to text routing
            }

            // ─── Text routing ─────────────────────────────────────
            $normalized = strtolower(trim($body));
            $reply = match (true) {
                in_array($normalized, ['clock in', 'clockin', 'in', 'start', 'arrived'], true)
                    => $this->handleClockIn($message, $employee),
                in_array($normalized, ['clock out', 'clockout', 'out', 'done', 'leaving', 'end'], true)
                    => $this->handleClockOut($message, $employee),
                in_array($normalized, ['status', 'where', '?'], true)
                    => $this->handleStatus($message, $employee),
                in_array($normalized, ['help', 'commands', 'hi', 'hello', ''], true)
                    => $this->handleHelp($message, $employee),
                default
                    => $this->handleUnknownText($message, $employee, $body),
            };

            return $reply;
        } catch (\Throwable $e) {
            Log::error('Twilio router error', ['exception' => $e->getMessage(), 'msg_id' => $message->id]);
            $reply = "Sorry — something went wrong on our side. The office has been notified.";
            $message->update(['intent' => 'error', 'status' => 'failed', 'reply' => $reply, 'error' => $e->getMessage()]);
            return $reply;
        }
    }

    // ─── Phone → Employee lookup ──────────────────────────────────
    private function resolveEmployee(string $from): ?Employee
    {
        $digits = preg_replace('/\D+/', '', $from);
        if ($digits === '') return null;
        $last10 = substr($digits, -10);

        return Employee::where('status', 'active')
            ->where(function ($q) use ($last10) {
                $q->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(personal_cell, '-', ''), ' ', ''), '(', ''), ')', '') LIKE ?", ['%' . $last10])
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(work_cell, '-', ''), ' ', ''), '(', ''), ')', '') LIKE ?",    ['%' . $last10])
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '(', ''), ')', '') LIKE ?",        ['%' . $last10]);
            })
            ->first();
    }

    // ─── Image handler: Snap-a-Timesheet via SMS ──────────────────
    private function handleImage(TwilioMessage $msg, Employee $emp, array $media): string
    {
        // Twilio media URLs need basic auth with the Account SID + auth token
        $sid  = config('services.twilio.sid');
        $auth = config('services.twilio.auth_token');
        $resp = Http::withBasicAuth($sid ?? '', $auth ?? '')->timeout(20)->get($media['url']);
        if (! $resp->successful()) {
            throw new \RuntimeException('Could not download media (HTTP ' . $resp->status() . ')');
        }

        $b64 = base64_encode($resp->body());
        $type = $media['content_type'] ?: 'image/jpeg';
        $result = $this->timesheetOcr->extractFromImage($b64, $type);
        $rowCount = count($result['entries'] ?? []);

        // Don't auto-commit — surface to the office for review. We just save
        // the scan log + a draft timesheet so it's discoverable later.
        $reply = "Got your photo. AI extracted {$rowCount} row(s) — the office will review them in the app and approve before payroll. Thanks!";

        $msg->update([
            'intent' => 'timesheet_photo',
            'status' => 'processed',
            'reply'  => $reply,
        ]);
        return $reply;
    }

    // ─── Audio handler: voice → transcribe → daily log AI ─────────
    private function handleAudio(TwilioMessage $msg, Employee $emp, array $media): string
    {
        $sid  = config('services.twilio.sid');
        $auth = config('services.twilio.auth_token');
        $resp = Http::withBasicAuth($sid ?? '', $auth ?? '')->timeout(20)->get($media['url']);
        if (! $resp->successful()) {
            throw new \RuntimeException('Could not download audio (HTTP ' . $resp->status() . ')');
        }

        // Send audio to Groq Whisper for transcription.
        $transcript = $this->transcribeWithGroq($resp->body(), $media['content_type'] ?: 'audio/mpeg');
        if ($transcript === '') {
            $reply = "Got your voice note but couldn't transcribe it. Try recording again in a quieter spot?";
            $msg->update(['intent' => 'daily_log_voice', 'status' => 'failed', 'reply' => $reply, 'error' => 'empty transcript']);
            return $reply;
        }

        // Resolve the employee's most-recent active project (their last
        // timesheet's project is a good guess — they're probably still there).
        $project = $this->guessActiveProjectForEmployee($emp);
        if (! $project) {
            $reply = "Got your voice note: \"" . \Illuminate\Support\Str::limit($transcript, 120) . "\" — but I don't know which project to log this against. Ask the office to assign you to a project, then resend.";
            $msg->update(['intent' => 'daily_log_voice', 'status' => 'failed', 'reply' => $reply, 'error' => 'no project guess']);
            return $reply;
        }

        $parsed = $this->dailyLogAi->extractFromTranscript($transcript);
        $fields = $parsed['fields'] ?? [];

        // Create the DailyLog directly — the AI handled the structured part.
        $log = DailyLog::create([
            'project_id'        => $project->id,
            'date'              => now()->toDateString(),
            'weather'           => $fields['weather'] ?: 'cloudy',
            'temperature'       => $fields['temperature'],
            'temperature_high'  => $fields['temperature_high'],
            'temperature_low'   => $fields['temperature_low'],
            'precipitation'     => $fields['precipitation'],
            'wind_speed'        => $fields['wind_speed'],
            'notes'             => $fields['notes'] ?: $transcript,
            'visitors'          => $fields['visitors'],
            'safety_issues'     => $fields['safety_issues'],
            'incidents_count'   => $fields['incidents_count'] ?? 0,
            'near_misses_count' => $fields['near_misses_count'] ?? 0,
            'delays'            => $fields['delays'],
            'created_by'        => null, // Twilio originated — no logged-in user
        ]);

        $reply = "Daily log filed for {$project->project_number}. Office can edit if anything needs a tweak.";
        $msg->update([
            'intent'       => 'daily_log_voice',
            'status'       => 'processed',
            'reply'        => $reply,
            'related_type' => DailyLog::class,
            'related_id'   => $log->id,
        ]);
        return $reply;
    }

    /**
     * Groq Whisper-large-v3 transcription. Audio bytes in → text out.
     */
    private function transcribeWithGroq(string $audioBytes, string $contentType): string
    {
        $apiKey = config('services.groq.api_key');
        if (empty($apiKey)) return '';

        $url   = rtrim((string) config('services.groq.base_url'), '/') . '/audio/transcriptions';
        $model = config('services.groq.whisper_model', 'whisper-large-v3');

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
            ->timeout(60)
            ->attach('file', $audioBytes, 'voice-note.' . $this->guessExtension($contentType))
            ->post($url, ['model' => $model, 'response_format' => 'text', 'language' => 'en']);

        if (! $response->successful()) {
            Log::error('Groq whisper error', ['status' => $response->status(), 'body' => $response->body()]);
            return '';
        }
        return trim((string) $response->body());
    }

    private function guessExtension(string $contentType): string
    {
        return match ($contentType) {
            'audio/mpeg', 'audio/mp3'   => 'mp3',
            'audio/ogg', 'audio/opus'   => 'ogg',
            'audio/wav', 'audio/wave'   => 'wav',
            'audio/m4a', 'audio/mp4'    => 'm4a',
            'audio/webm', 'video/webm'  => 'webm',
            'video/mp4'                 => 'm4a',
            default                     => 'm4a',
        };
    }

    private function guessActiveProjectForEmployee(Employee $emp): ?Project
    {
        $recent = Timesheet::where('employee_id', $emp->id)
            ->whereIn('status', ['draft', 'submitted', 'approved'])
            ->orderByDesc('date')
            ->first();
        if ($recent && $recent->project_id) {
            return Project::find($recent->project_id);
        }
        // Fall back to any crew assignment
        $crewEmp = \App\Models\CrewMember::where('employee_id', $emp->id)
            ->whereNull('removed_date')
            ->first();
        if ($crewEmp) {
            $crew = \App\Models\Crew::find($crewEmp->crew_id);
            return $crew?->project;
        }
        return null;
    }

    // ─── Text command: Clock In ───────────────────────────────────
    private function handleClockIn(TwilioMessage $msg, Employee $emp): string
    {
        $existing = TimeClockEntry::where('employee_id', $emp->id)->where('status', 'open')->first();
        if ($existing) {
            $reply = "You're already clocked in (since " . $existing->clock_in_at->format('g:i A') . "). Text 'out' when you're done.";
            $msg->update(['intent' => 'clock_in', 'status' => 'processed', 'reply' => $reply,
                'related_type' => TimeClockEntry::class, 'related_id' => $existing->id]);
            return $reply;
        }

        $project = $this->guessActiveProjectForEmployee($emp);
        $entry = TimeClockEntry::create([
            'employee_id' => $emp->id,
            'project_id'  => $project?->id,
            'clock_in_at' => now(),
            'status'      => 'open',
            'notes'       => 'Clocked in via SMS / WhatsApp',
        ]);

        $projLine = $project ? " on {$project->project_number}" : '';
        $reply = "Clocked in at " . now()->format('g:i A') . "{$projLine}. Have a safe day. Text 'out' when you leave.";
        $msg->update(['intent' => 'clock_in', 'status' => 'processed', 'reply' => $reply,
            'related_type' => TimeClockEntry::class, 'related_id' => $entry->id]);
        return $reply;
    }

    // ─── Text command: Clock Out ──────────────────────────────────
    private function handleClockOut(TwilioMessage $msg, Employee $emp): string
    {
        $open = TimeClockEntry::where('employee_id', $emp->id)->where('status', 'open')->first();
        if (! $open) {
            $reply = "I don't see an open clock-in for you. Text 'in' when you start your shift.";
            $msg->update(['intent' => 'clock_out', 'status' => 'failed', 'reply' => $reply]);
            return $reply;
        }
        $now = now();
        $hours = round($now->diffInMinutes($open->clock_in_at) / 60 * -1, 2); // diffInMinutes is signed
        $hours = abs($hours);
        $open->update([
            'clock_out_at' => $now,
            'hours'        => $hours,
            'status'       => 'closed',
        ]);

        $reply = "Clocked out at " . $now->format('g:i A') . ". {$hours} hr(s) logged. Thanks for the hard work.";
        $msg->update(['intent' => 'clock_out', 'status' => 'processed', 'reply' => $reply,
            'related_type' => TimeClockEntry::class, 'related_id' => $open->id]);
        return $reply;
    }

    private function handleStatus(TwilioMessage $msg, Employee $emp): string
    {
        $open = TimeClockEntry::where('employee_id', $emp->id)->where('status', 'open')->first();
        if ($open) {
            $reply = "You're clocked in since " . $open->clock_in_at->format('g:i A') . ".";
        } else {
            $reply = "You are not clocked in. Text 'in' when you start.";
        }
        $msg->update(['intent' => 'status', 'status' => 'processed', 'reply' => $reply]);
        return $reply;
    }

    private function handleHelp(TwilioMessage $msg, Employee $emp): string
    {
        $reply = "Hi " . $emp->first_name . "! Things I can do:\n"
              . "• 'in' — clock in\n"
              . "• 'out' — clock out\n"
              . "• 'status' — am I clocked in?\n"
              . "• Photo of timesheet → office gets the rows extracted\n"
              . "• Voice note → posts today's daily log\n"
              . "Reply 'help' to see this again.";
        $msg->update(['intent' => 'help', 'status' => 'processed', 'reply' => $reply]);
        return $reply;
    }

    private function handleUnknownText(TwilioMessage $msg, Employee $emp, string $body): string
    {
        $reply = "I'm not sure what to do with that. Reply 'help' to see what I can do.";
        $msg->update(['intent' => 'unknown', 'status' => 'processed', 'reply' => $reply]);
        return $reply;
    }

    private function replyUnknownNumber(): string
    {
        return "This number isn't registered with the construction office. "
            . "Ask your foreman or admin to add your cell number to your employee record, then try again.";
    }
}
