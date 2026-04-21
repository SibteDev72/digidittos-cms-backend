<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactRequestMail;
use App\Models\SiteSetting;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles public /contact form submissions. Delivers a branded email
 * through the configured Mailtrap SMTP transport (`MAIL_*` in .env)
 * to the address stored in CMS Website Settings → Contact. Falls back
 * to `MAIL_CONTACT_TO` env and then a hardcoded default so a missing
 * CMS value doesn't black-hole leads.
 */
class ContactController extends Controller
{
    use LogsActivity;

    private const DEFAULT_TO = 'contactus@digidittos.com';

    /**
     * Public: accept a contact form submission and email it to the team.
     *
     * Expected JSON payload (all strings):
     *   { name, email, company?, service, budget?, message }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:150',
            'email'   => 'required|email|max:255',
            'company' => 'nullable|string|max:200',
            'service' => 'required|string|max:150',
            'budget'  => 'nullable|string|max:100',
            'message' => 'required|string|max:5000',
        ]);

        $to = $this->resolveRecipient();

        try {
            Mail::to($to)->send(new ContactRequestMail($validated));

            $this->logActivity(
                'contact_submitted',
                "Contact form: {$validated['name']} <{$validated['email']}> — {$validated['service']}",
            );

            return response()->json([
                'success' => true,
                'message' => 'Thanks — your message was sent successfully. We\'ll reply within 24 hours.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Contact form email failed: ' . $e->getMessage(), [
                'to' => $to,
                'from' => $validated['email'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Couldn\'t send your message right now. Please email us directly.',
            ], 502);
        }
    }

    /**
     * Priority: CMS footer.contact_email → env MAIL_CONTACT_TO → hardcoded.
     */
    private function resolveRecipient(): string
    {
        $fromCms = SiteSetting::where('group', 'footer')
            ->where('key', 'contact_email')
            ->value('value');

        if (is_string($fromCms) && trim($fromCms) !== '' && filter_var($fromCms, FILTER_VALIDATE_EMAIL)) {
            return $fromCms;
        }

        $fromEnv = env('MAIL_CONTACT_TO');
        if (is_string($fromEnv) && filter_var($fromEnv, FILTER_VALIDATE_EMAIL)) {
            return $fromEnv;
        }

        return self::DEFAULT_TO;
    }
}
