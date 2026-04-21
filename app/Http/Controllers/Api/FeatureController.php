<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeatureController extends Controller
{
    /**
     * List all features with media and assigned services.
     */
    public function index(): JsonResponse
    {
        $features = ServiceFeature::with(['media', 'services:id,title,slug'])
            ->ordered()
            ->get();

        return response()->json(['features' => $features]);
    }

    /**
     * Create a new feature.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateFeature($request);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $mediaData = $validated['media'] ?? [];
        unset($validated['media']);

        $feature = ServiceFeature::create($validated);

        foreach ($mediaData as $index => $media) {
            $media['sort_order'] = $index;
            $feature->media()->create($media);
        }

        $feature->load(['media', 'services:id,title,slug']);

        return response()->json([
            'message' => 'Feature created successfully.',
            'feature' => $feature,
        ], 201);
    }

    /**
     * Show a single feature.
     */
    public function show($id): JsonResponse
    {
        $feature = ServiceFeature::with(['media', 'services:id,title,slug'])
            ->findOrFail($id);

        return response()->json(['feature' => $feature]);
    }

    /**
     * Update a feature.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $feature = ServiceFeature::findOrFail($id);

        $validated = $this->validateFeature($request, $feature->id);

        $mediaData = $validated['media'] ?? null;
        unset($validated['media']);

        if (!empty($validated['slug'])) {
            $validated['slug'] = $validated['slug'];
        }

        $feature->update($validated);

        // Sync media: delete all existing, recreate
        if ($mediaData !== null) {
            $feature->media()->delete();
            foreach ($mediaData as $index => $media) {
                $media['sort_order'] = $index;
                $feature->media()->create($media);
            }
        }

        $feature->load(['media', 'services:id,title,slug']);

        return response()->json([
            'message' => 'Feature updated successfully.',
            'feature' => $feature,
        ]);
    }

    /**
     * Delete a feature (cascades media and pivot assignments).
     */
    public function destroy($id): JsonResponse
    {
        $feature = ServiceFeature::findOrFail($id);

        // Detach from all services (pivot)
        $feature->services()->detach();

        // Delete media (cascade should handle this, but be explicit)
        $feature->media()->delete();

        $feature->delete();

        return response()->json([
            'message' => 'Feature deleted successfully.',
        ]);
    }

    /**
     * Validate feature request data.
     */
    private function validateFeature(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = $ignoreId
            ? ['nullable', 'string', 'max:255', \Illuminate\Validation\Rule::unique('service_features', 'slug')->ignore($ignoreId)]
            : ['nullable', 'string', 'max:255', 'unique:service_features,slug'];

        $featureKeyRule = $ignoreId
            ? ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('service_features', 'feature_key')->ignore($ignoreId)]
            : ['required', 'string', 'max:255', 'unique:service_features,feature_key'];

        return $request->validate([
            'feature_key'        => $featureKeyRule,
            'slug'               => $slugRule,
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'items'              => 'nullable|array',
            'items.*'            => 'string',
            'is_active'          => 'nullable|boolean',

            // Hero block
            'headline'           => 'nullable|string|max:500',
            'hero_description'   => 'nullable|string',

            // Overview
            'overview_title'     => 'nullable|string|max:255',
            'overview_description' => 'nullable|string',
            'overview'           => 'nullable|array',
            'overview.*.title'   => 'required_with:overview|string|max:255',
            'overview.*.desc'    => 'nullable|string',

            // Approach (stored in legacy process_* columns)
            'process_title'      => 'nullable|string|max:255',
            'process_steps'      => 'nullable|array',
            'process_steps.*.title'       => 'required_with:process_steps|string|max:255',
            'process_steps.*.description' => 'nullable|string',

            // Technologies
            'technologies'           => 'nullable|array',
            'technologies.*.name'    => 'required_with:technologies|string|max:100',
            'technologies.*.category' => 'nullable|string|max:100',

            // CTA override
            'cta_title'          => 'nullable|string|max:255',
            'cta_description'    => 'nullable|string',
            'cta_button_text'    => 'nullable|string|max:100',
            'cta_button_url'     => 'nullable|string|max:500',

            // SEO
            'meta_title'         => 'nullable|string|max:255',
            'meta_description'   => 'nullable|string|max:500',
            'meta_keywords'      => 'nullable|array',
            'meta_keywords.*'    => 'string|max:100',
            'og_title'           => 'nullable|string|max:255',
            'og_description'     => 'nullable|string|max:500',
            'og_image'           => 'nullable|string|max:500',
            'json_ld_schema'     => 'nullable',

            // Legacy
            'field_types'        => 'nullable|array',
            'field_types.*'      => 'string|max:255',

            // Media
            'media'              => 'nullable|array',
            'media.*.type'       => 'required_with:media|string|in:video,image',
            'media.*.primary_url' => 'required_with:media|string',
            'media.*.secondary_url' => 'nullable|string',
        ]);
    }

    // ─── PUBLIC ENDPOINT ─────────────────────────────────────────────

    /**
     * GET /api/public/features/{slug} - single active feature with
     * its parent service info (first assigned) for the business site's
     * /services/{slug} detail page.
     */
    public function publicShow(string $slug): JsonResponse
    {
        $feature = ServiceFeature::active()
            ->with(['services:id,title,short_title,slug,route'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $feature]);
    }
}
