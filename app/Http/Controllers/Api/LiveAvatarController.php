<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\LiveAvatarService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveAvatarController extends Controller
{
    use LogsActivity;

    public function __construct(protected LiveAvatarService $liveAvatar)
    {
    }

    // ─── PUBLIC ───────────────────────────────────────────────

    /**
     * Public: returns the embed URL for the LiveAvatar widget.
     * The frontend calls this to get the embed iframe URL.
     */
    public function publicEmbed(): JsonResponse
    {
        $embedUrl = config('services.liveavatar.embed_url');

        return response()->json([
            'embed_url' => $embedUrl,
            'display_name' => SiteSetting::getValue('liveavatar_display_name', 'DigiDittos'),
            'subheading' => SiteSetting::getValue('liveavatar_subheading', ''),
        ]);
    }

    /**
     * Admin: get the widget display config (name + subheading).
     */
    public function getDisplayConfig(): JsonResponse
    {
        return response()->json([
            'display_name' => SiteSetting::getValue('liveavatar_display_name', 'DigiDittos'),
            'subheading' => SiteSetting::getValue('liveavatar_subheading', ''),
        ]);
    }

    /**
     * Admin: update the widget display config.
     */
    public function updateDisplayConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:100',
            'subheading' => 'nullable|string|max:100',
        ]);

        SiteSetting::setValue('liveavatar_display_name', $validated['display_name'], 'string', 'integrations');
        SiteSetting::setValue('liveavatar_subheading', $validated['subheading'] ?? '', 'string', 'integrations');

        $this->logActivity('liveavatar_display_updated', 'LiveAvatar widget display config updated.');

        return response()->json([
            'message' => 'Widget display config updated successfully.',
            'display_name' => $validated['display_name'],
            'subheading' => $validated['subheading'],
        ]);
    }

    // ─── ADMIN ───────────────────────────────────────────────

    /**
     * Sync CMS content to the LiveAvatar context.
     * Call this after updating services, pricing, about, or site settings
     * to keep the AI avatar's knowledge up-to-date.
     */
    public function syncContext(): JsonResponse
    {
        $result = $this->liveAvatar->syncContext();

        if ($result['success']) {
            $this->logActivity('liveavatar.sync', 'Synced CMS data to LiveAvatar context');
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Preview the context prompt that would be sent to LiveAvatar.
     * Useful for admins to review what the AI avatar will know.
     */
    public function previewContext(): JsonResponse
    {
        return response()->json([
            'prompt' => $this->liveAvatar->buildContextPrompt(),
            'opening_text' => $this->liveAvatar->buildOpeningText(),
        ]);
    }

    /**
     * Get the current context data from LiveAvatar API.
     */
    public function getContext(): JsonResponse
    {
        $result = $this->liveAvatar->getContext();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Manually update the context on LiveAvatar.
     * Allows admins to edit the prompt and opening text directly.
     */
    public function updateContext(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'prompt' => 'required|string',
            'opening_text' => 'required|string',
        ]);

        $result = $this->liveAvatar->updateContext(
            $request->input('name'),
            $request->input('prompt'),
            $request->input('opening_text'),
        );

        if ($result['success']) {
            $this->logActivity('liveavatar.update', 'Manually updated LiveAvatar context');
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Regenerate the embed URL for the LiveAvatar widget.
     */
    public function regenerateEmbed(): JsonResponse
    {
        $result = $this->liveAvatar->createEmbed();

        if ($result['success']) {
            // Store the new embed URL in site settings
            \App\Models\SiteSetting::setValue('liveavatar_embed_url', $result['embed_url'], 'string', 'integrations');
            $this->logActivity('liveavatar.embed', 'Regenerated LiveAvatar embed URL');
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
