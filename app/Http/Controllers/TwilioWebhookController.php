<?php

namespace App\Http\Controllers;

use App\Services\TwilioRouter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * 2026-05-12 (Brenda — Phase 5 / WhatsApp + SMS bot).
 *
 * Single inbound webhook for Twilio messages. Delegates intent routing
 * to TwilioRouter and wraps the resulting string in TwiML so Twilio
 * sends the reply back to the foreman.
 *
 * Wired in routes/web.php as a top-level POST with explicit CSRF
 * exemption (Twilio doesn't have a CSRF token).
 */
class TwilioWebhookController extends Controller
{
    public function incoming(Request $request, TwilioRouter $router): Response
    {
        // Optional but recommended: verify the X-Twilio-Signature header so
        // random people can't spam our webhook URL. Toggle off via
        // TWILIO_VALIDATE_SIGNATURE=false for local ngrok / Postman testing.
        if ((bool) config('services.twilio.validate_signature', true)) {
            if (! $this->isValidSignature($request)) {
                Log::warning('Twilio webhook signature mismatch', [
                    'from' => $request->input('From'),
                    'sig'  => $request->header('X-Twilio-Signature'),
                ]);
                return response('Invalid signature', 403);
            }
        }

        $replyText = $router->handle($request);
        return response($this->twiml($replyText), 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    /**
     * Twilio's request-signature scheme — HMAC-SHA1 of (full URL + sorted
     * post params) signed with the account auth token. Cheap to verify and
     * blocks the easy spam vector.
     */
    private function isValidSignature(Request $request): bool
    {
        $auth = config('services.twilio.auth_token');
        if (empty($auth)) return false;

        $sig = $request->header('X-Twilio-Signature');
        if (empty($sig)) return false;

        $url = $request->fullUrl();
        $params = $request->post();
        ksort($params);
        $payload = $url;
        foreach ($params as $k => $v) {
            $payload .= $k . (is_array($v) ? implode('', $v) : $v);
        }
        $expected = base64_encode(hash_hmac('sha1', $payload, $auth, true));
        return hash_equals($expected, $sig);
    }

    /**
     * Wrap a reply string in TwiML. Twilio expects a `<Response><Message>...`
     * payload that gets sent back to the original sender.
     */
    private function twiml(string $message): string
    {
        $escaped = htmlspecialchars($message, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Response><Message>' . $escaped . '</Message></Response>';
    }
}
