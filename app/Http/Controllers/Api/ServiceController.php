<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceDetail;
use App\Models\ServiceFeature;
use App\Models\ServiceFeatureMedia;
use App\Models\ServiceProcessStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    use LogsActivity;
    // ─── PUBLIC ───────────────────────────────────────────────

    /**
     * Public endpoint: returns active services with basic info.
     *
     * Eager-loads each service's active features (with slug, title, description,
     * hero fields) so the business site's /services page can render the
     * parent → feature grouping in a single round trip.
     */
    public function publicIndex(): JsonResponse
    {
        $services = Service::active()
            ->ordered()
            ->with(['features' => function ($q) {
                $q->where('is_active', true)
                  ->orderBy('service_feature_assignments.sort_order');
            }])
            ->get([
                'id', 'number', 'title', 'short_title', 'slug',
                'description', 'video_url', 'route', 'is_available',
                'meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'json_ld_schema',
            ]);

        return response()->json(['services' => $services]);
    }

    /**
     * Public endpoint: returns full service detail by slug.
     */
    public function publicShow(string $slug): JsonResponse
    {
        $service = Service::where('slug', $slug)->active()
            ->with(['detail', 'features.media', 'processSteps'])
            ->firstOrFail();

        return response()->json(['service' => $service]);
    }

    // ─── ADMIN ────────────────────────────────────────────────

    /**
     * List all services with details.
     */
    public function index(): JsonResponse
    {
        $services = Service::ordered()
            ->with(['detail', 'features.media', 'processSteps'])
            ->get();

        return response()->json(['services' => $services]);
    }

    /**
     * Get a single service with all nested data.
     */
    public function show($id): JsonResponse
    {
        $service = Service::with(['detail', 'features.media', 'processSteps'])
            ->findOrFail($id);

        return response()->json(['service' => $service]);
    }

    /**
     * Create a service with all nested data.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateService($request);

        $service = DB::transaction(function () use ($validated) {
            $serviceData = collect($validated)->only([
                'number', 'title', 'short_title', 'slug', 'description',
                'video_url', 'route', 'is_available', 'sort_order', 'is_active',
                'meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'json_ld_schema',
            ])->toArray();

            $service = Service::create($serviceData);

            if (!empty($validated['detail'])) {
                $service->detail()->create($validated['detail']);
            }

            if (isset($validated['feature_ids'])) {
                $syncData = [];
                foreach ($validated['feature_ids'] as $order => $featureId) {
                    $syncData[$featureId] = ['sort_order' => $order];
                }
                $service->features()->sync($syncData);
            }

            if (!empty($validated['process_steps'])) {
                $this->syncProcessSteps($service, $validated['process_steps']);
            }

            return $service->load(['detail', 'features.media', 'processSteps']);
        });

        $this->logActivity('service_created', "Service \"{$service->title}\" was created.");

        return response()->json([
            'message' => 'Service created successfully.',
            'service' => $service,
        ], 201);
    }

    /**
     * Update a service with all nested data.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        $validated = $this->validateService($request, $service->id);

        $service = DB::transaction(function () use ($service, $validated) {
            $serviceData = collect($validated)->only([
                'number', 'title', 'short_title', 'slug', 'description',
                'video_url', 'route', 'is_available', 'sort_order', 'is_active',
                'meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'json_ld_schema',
            ])->toArray();

            $service->update($serviceData);

            if (array_key_exists('detail', $validated)) {
                if ($validated['detail']) {
                    $service->detail()
                        ? $service->detail()->updateOrCreate(
                            ['service_id' => $service->id],
                            $validated['detail']
                        )
                        : $service->detail()->create($validated['detail']);
                }
            }

            if (isset($validated['feature_ids'])) {
                $syncData = [];
                foreach ($validated['feature_ids'] as $order => $featureId) {
                    $syncData[$featureId] = ['sort_order' => $order];
                }
                $service->features()->sync($syncData);
            }

            if (array_key_exists('process_steps', $validated)) {
                $this->syncProcessSteps($service, $validated['process_steps'] ?? []);
            }

            return $service->load(['detail', 'features.media', 'processSteps']);
        });

        $this->logActivity('service_updated', "Service \"{$service->title}\" was updated.");

        return response()->json([
            'message' => 'Service updated successfully.',
            'service' => $service,
        ]);
    }

    /**
     * Delete a service (cascades).
     */
    public function destroy($id): JsonResponse
    {
        $service = Service::findOrFail($id);
        $this->logActivity('service_deleted', "Service \"{$service->title}\" was deleted.");
        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully.',
        ]);
    }

    /**
     * Reorder services.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_ids' => 'required|array',
            'service_ids.*' => 'integer|exists:services,id',
        ]);

        foreach ($validated['service_ids'] as $index => $serviceId) {
            Service::where('id', $serviceId)->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'message' => 'Services reordered successfully.',
        ]);
    }

    // ─── PRIVATE HELPERS ──────────────────────────────────────

    private function validateService(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = $ignoreId
            ? ['sometimes', 'required', 'string', 'max:255', Rule::unique('services')->ignore($ignoreId)]
            : ['required', 'string', 'max:255', 'unique:services,slug'];

        return $request->validate([
            'number' => ($ignoreId ? 'sometimes|' : '') . 'required|string|max:10',
            'title' => ($ignoreId ? 'sometimes|' : '') . 'required|string|max:255',
            'short_title' => ($ignoreId ? 'sometimes|' : '') . 'required|string|max:255',
            'slug' => $slugRule,
            'description' => ($ignoreId ? 'sometimes|' : '') . 'required|string',
            'video_url' => 'nullable|string|max:500',
            'route' => ($ignoreId ? 'sometimes|' : '') . 'required|string|max:255',
            'is_available' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',

            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'json_ld_schema' => 'nullable',

            'detail' => 'nullable|array',
            'detail.hero_label' => 'required_with:detail|string|max:255',
            'detail.hero_headline' => 'required_with:detail|array|min:1|max:5',
            'detail.hero_headline.*' => 'string',
            'detail.hero_subtitle' => 'required_with:detail|string',
            'detail.media_type' => 'required_with:detail|string|in:video,image',
            'detail.process_title' => 'nullable|string|max:255',
            'detail.cta_title' => 'nullable|string|max:255',
            'detail.cta_description' => 'nullable|string',
            'detail.cta_button_text' => 'nullable|string|max:255',
            'detail.is_coming_soon' => 'nullable|boolean',
            'detail.coming_soon_description' => 'nullable|string',
            'detail.field_types' => 'nullable|array',
            'detail.field_types.*' => 'string',

            'feature_ids' => 'nullable|array',
            'feature_ids.*' => 'integer|exists:service_features,id',

            'process_steps' => 'nullable|array',
            'process_steps.*.title' => 'required|string|max:255',
            'process_steps.*.description' => 'required|string',
            'process_steps.*.sort_order' => 'nullable|integer',
        ]);
    }

    private function syncProcessSteps(Service $service, array $steps): void
    {
        $service->processSteps()->delete();

        foreach ($steps as $index => $step) {
            $step['sort_order'] = $step['sort_order'] ?? $index;
            $service->processSteps()->create($step);
        }
    }
}
