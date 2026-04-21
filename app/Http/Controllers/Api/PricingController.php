<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PricingCategorySale;
use App\Models\PricingFaq;
use App\Models\PricingPlan;
use App\Models\PricingPlanPrice;
use App\Models\PricingSetting;
use App\Models\SiteSetting;
use App\Services\SquareCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Traits\LogsActivity;

class PricingController extends Controller
{
    use LogsActivity;
    // ─── PUBLIC ───────────────────────────────────────────────

    /**
     * Public endpoint: returns active plans, FAQs, and settings.
     */
    public function publicIndex(): JsonResponse
    {
        $plans = PricingPlan::active()->ordered()->with(['prices' => fn($q) => $q->where('is_active', true)])->get();

        $formattedPlans = $plans->map(function ($plan) {
            $prices = [];
            foreach (['monthly', 'annual'] as $period) {
                $priceModel = $plan->prices->firstWhere('billing_period', $period);
                if ($priceModel) {
                    $effective = $priceModel->getEffectivePrice();
                    $prices[$period] = $effective;
                }
            }
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'is_highlighted' => $plan->is_highlighted,
                'highlight_label' => $plan->highlight_label,
                'cta_text' => $plan->cta_text,
                'cta_url' => $plan->cta_url,
                'features' => $plan->features,
                'custom_price_label' => $plan->custom_price_label,
                'prices' => $prices,
            ];
        });

        $categorySales = [];
        foreach (['monthly', 'annual'] as $period) {
            $sale = PricingCategorySale::getActiveSaleFor($period);
            $categorySales[$period] = $sale ? [
                'label' => $sale->label,
                'discount_percentage' => $sale->discount_percentage,
            ] : null;
        }

        $settings = [
            'hero_label' => PricingSetting::getValue('pricing_hero_label', 'PRICING'),
            'hero_headline' => PricingSetting::getValue('pricing_hero_headline', ['Simple,', 'transparent', 'pricing']),
            'hero_subtitle' => PricingSetting::getValue('pricing_hero_subtitle', ''),
            'comparison_label' => PricingSetting::getValue('pricing_comparison_label', 'COMPARE PLANS'),
            'comparison_headline' => PricingSetting::getValue('pricing_comparison_headline', 'All Plans Include'),
            'comparison_features' => PricingSetting::getValue('pricing_comparison_features', []),
            'cta_headline' => PricingSetting::getValue('pricing_cta_headline', 'Ready to get started?'),
            'cta_subtitle' => PricingSetting::getValue('pricing_cta_subtitle', ''),
            'cta_primary_text' => PricingSetting::getValue('pricing_cta_primary_text', 'Start Free Trial'),
            'cta_primary_url' => PricingSetting::getValue('pricing_cta_primary_url', '/#contact'),
            'cta_secondary_text' => PricingSetting::getValue('pricing_cta_secondary_text', 'Contact Sales'),
            'cta_secondary_url' => PricingSetting::getValue('pricing_cta_secondary_url', '/#contact'),
        ];

        // Page-specific SEO with global fallback
        $pageSeo = SiteSetting::getGroup('seo_pricing');
        $globalSeo = SiteSetting::getGroup('seo');
        $seo = [];
        foreach (['meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'json_ld_schema'] as $key) {
            $pageVal = $pageSeo[$key] ?? null;
            $seo[$key] = (!empty($pageVal)) ? $pageVal : ($globalSeo[$key] ?? null);
        }

        return response()->json([
            'plans' => $formattedPlans,
            'category_sales' => $categorySales,
            'settings' => $settings,
            'seo' => $seo,
        ]);
    }

    // ─── ADMIN: PLANS ────────────────────────────────────────

    /**
     * List all plans (including inactive).
     */
    public function plans(): JsonResponse
    {
        $plans = PricingPlan::ordered()->with('prices')->get();

        return response()->json(['plans' => $plans]);
    }

    /**
     * Create a new plan.
     */
    public function storePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pricing_plans,slug',
            'description' => 'nullable|string',
            'custom_price_label' => 'nullable|string|max:255',
            'is_highlighted' => 'nullable|boolean',
            'highlight_label' => 'nullable|string|max:255',
            'cta_text' => 'required|string|max:255',
            'cta_url' => 'nullable|string|max:255',
            'features' => 'required|array',
            'features.*' => 'string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'prices' => 'nullable|array',
            'prices.monthly' => 'nullable|array',
            'prices.monthly.price' => 'required_with:prices.monthly|numeric|min:0',
            'prices.monthly.currency' => 'nullable|string|max:10',
            'prices.monthly.sale_price' => 'nullable|numeric|min:0',
            'prices.monthly.sale_percentage' => 'nullable|integer|min:0|max:100',
            'prices.monthly.sale_label' => 'nullable|string|max:255',
            'prices.annual' => 'nullable|array',
            'prices.annual.price' => 'required_with:prices.annual|numeric|min:0',
            'prices.annual.currency' => 'nullable|string|max:10',
            'prices.annual.sale_price' => 'nullable|numeric|min:0',
            'prices.annual.sale_percentage' => 'nullable|integer|min:0|max:100',
            'prices.annual.sale_label' => 'nullable|string|max:255',
        ]);

        $plan = DB::transaction(function () use ($validated) {
            $planData = collect($validated)->except('prices')->toArray();
            $plan = PricingPlan::create($planData);

            if (!empty($validated['prices'])) {
                foreach (['monthly', 'annual'] as $period) {
                    if (!empty($validated['prices'][$period])) {
                        $priceData = $validated['prices'][$period];
                        PricingPlanPrice::updateOrCreate(
                            ['pricing_plan_id' => $plan->id, 'billing_period' => $period],
                            [
                                'price' => $priceData['price'],
                                'currency' => $priceData['currency'] ?? '$',
                                'sale_price' => $priceData['sale_price'] ?? null,
                                'sale_percentage' => $priceData['sale_percentage'] ?? null,
                                'sale_label' => $priceData['sale_label'] ?? null,
                            ]
                        );
                    }
                }
            }

            return $plan->load('prices');
        });

        // Sync plan prices to Square catalog (non-blocking — don't fail plan creation if Square is down)
        $this->syncPlanToSquare($plan);

        $this->logActivity('plan_created', "Pricing plan \"{$plan->name}\" was created.");

        return response()->json([
            'message' => 'Plan created successfully.',
            'plan' => $plan->fresh('prices'),
        ], 201);
    }

    /**
     * Get a single plan.
     */
    public function showPlan($id): JsonResponse
    {
        $plan = PricingPlan::with('prices')->findOrFail($id);

        return response()->json(['plan' => $plan]);
    }

    /**
     * Update a plan.
     */
    public function updatePlan(Request $request, $id): JsonResponse
    {
        $plan = PricingPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('pricing_plans')->ignore($plan->id)],
            'description' => 'nullable|string',
            'custom_price_label' => 'nullable|string|max:255',
            'is_highlighted' => 'nullable|boolean',
            'highlight_label' => 'nullable|string|max:255',
            'cta_text' => 'sometimes|required|string|max:255',
            'cta_url' => 'nullable|string|max:255',
            'features' => 'sometimes|required|array',
            'features.*' => 'string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'prices' => 'nullable|array',
            'prices.monthly' => 'nullable|array',
            'prices.monthly.price' => 'required_with:prices.monthly|numeric|min:0',
            'prices.monthly.currency' => 'nullable|string|max:10',
            'prices.monthly.sale_price' => 'nullable|numeric|min:0',
            'prices.monthly.sale_percentage' => 'nullable|integer|min:0|max:100',
            'prices.monthly.sale_label' => 'nullable|string|max:255',
            'prices.annual' => 'nullable|array',
            'prices.annual.price' => 'required_with:prices.annual|numeric|min:0',
            'prices.annual.currency' => 'nullable|string|max:10',
            'prices.annual.sale_price' => 'nullable|numeric|min:0',
            'prices.annual.sale_percentage' => 'nullable|integer|min:0|max:100',
            'prices.annual.sale_label' => 'nullable|string|max:255',
        ]);

        $plan = DB::transaction(function () use ($plan, $validated) {
            $planData = collect($validated)->except('prices')->toArray();
            $plan->update($planData);

            if (!empty($validated['prices'])) {
                foreach (['monthly', 'annual'] as $period) {
                    if (!empty($validated['prices'][$period])) {
                        $priceData = $validated['prices'][$period];
                        PricingPlanPrice::updateOrCreate(
                            ['pricing_plan_id' => $plan->id, 'billing_period' => $period],
                            [
                                'price' => $priceData['price'],
                                'currency' => $priceData['currency'] ?? '$',
                                'sale_price' => $priceData['sale_price'] ?? null,
                                'sale_percentage' => $priceData['sale_percentage'] ?? null,
                                'sale_label' => $priceData['sale_label'] ?? null,
                            ]
                        );
                    }
                }
            }

            return $plan->load('prices');
        });

        // Sync updated prices to Square catalog
        $this->syncPlanToSquare($plan);

        $this->logActivity('plan_updated', "Pricing plan \"{$plan->name}\" was updated.");

        return response()->json([
            'message' => 'Plan updated successfully.',
            'plan' => $plan->fresh('prices'),
        ]);
    }

    /**
     * Delete a plan.
     */
    public function destroyPlan($id): JsonResponse
    {
        $plan = PricingPlan::findOrFail($id);
        $this->logActivity('plan_deleted', "Pricing plan \"{$plan->name}\" was deleted.");
        $plan->delete();

        return response()->json([
            'message' => 'Plan deleted successfully.',
        ]);
    }

    /**
     * Reorder plans.
     */
    public function reorderPlans(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'integer|exists:pricing_plans,id',
        ]);

        foreach ($validated['plan_ids'] as $index => $planId) {
            PricingPlan::where('id', $planId)->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'message' => 'Plans reordered successfully.',
        ]);
    }

    // ─── ADMIN: FAQS ─────────────────────────────────────────

    /**
     * List all FAQs (including inactive).
     */
    public function faqs(): JsonResponse
    {
        $faqs = PricingFaq::ordered()->get();

        return response()->json(['faqs' => $faqs]);
    }

    /**
     * Create a new FAQ.
     */
    public function storeFaq(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = PricingFaq::create($validated);

        $this->logActivity('faq_created', "Pricing FAQ was created.");

        return response()->json([
            'message' => 'FAQ created successfully.',
            'faq' => $faq,
        ], 201);
    }

    /**
     * Update a FAQ.
     */
    public function updateFaq(Request $request, $id): JsonResponse
    {
        $faq = PricingFaq::findOrFail($id);

        $validated = $request->validate([
            'question' => 'sometimes|required|string|max:255',
            'answer' => 'sometimes|required|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $faq->update($validated);

        $this->logActivity('faq_updated', "Pricing FAQ was updated.");

        return response()->json([
            'message' => 'FAQ updated successfully.',
            'faq' => $faq,
        ]);
    }

    /**
     * Delete a FAQ.
     */
    public function destroyFaq($id): JsonResponse
    {
        $faq = PricingFaq::findOrFail($id);
        $this->logActivity('faq_deleted', "Pricing FAQ was deleted.");
        $faq->delete();

        return response()->json([
            'message' => 'FAQ deleted successfully.',
        ]);
    }

    // ─── ADMIN: SETTINGS ─────────────────────────────────────

    /**
     * Get all pricing settings.
     */
    public function settings(): JsonResponse
    {
        $settings = PricingSetting::all()->pluck('value', 'key');

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update pricing settings (bulk).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $existing = PricingSetting::where('key', $key)->first();
            $type = $existing ? $existing->type : 'string';
            PricingSetting::setValue($key, $value, $type);
        }

        $this->logActivity('settings_updated', "Pricing settings were updated.");

        return response()->json([
            'message' => 'Settings updated successfully.',
        ]);
    }

    // ─── ADMIN: CATEGORY SALES ──────────────────────────────

    /**
     * List all category sales.
     */
    public function categorySales(): JsonResponse
    {
        $sales = PricingCategorySale::orderByDesc('priority')->get();

        return response()->json(['category_sales' => $sales]);
    }

    /**
     * Create a new category sale.
     */
    public function storeCategorySale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'billing_period' => 'required|string|in:monthly,annual',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'label' => 'nullable|string|max:255',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
        ]);

        $sale = PricingCategorySale::create($validated);

        $this->logActivity('sale_created', "Category sale \"{$sale->name}\" was created.");

        // Category sale affects effective prices of all plans — sync to Square
        $this->syncAllPlansToSquare();

        return response()->json([
            'message' => 'Category sale created successfully.',
            'category_sale' => $sale,
        ], 201);
    }

    /**
     * Update a category sale.
     */
    public function updateCategorySale(Request $request, $id): JsonResponse
    {
        $sale = PricingCategorySale::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'billing_period' => 'sometimes|required|string|in:monthly,annual',
            'discount_percentage' => 'sometimes|required|integer|min:0|max:100',
            'label' => 'nullable|string|max:255',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
        ]);

        $sale->update($validated);

        $this->logActivity('sale_updated', "Category sale \"{$sale->name}\" was updated.");

        // Category sale affects effective prices of all plans — sync to Square
        $this->syncAllPlansToSquare();

        return response()->json([
            'message' => 'Category sale updated successfully.',
            'category_sale' => $sale,
        ]);
    }

    /**
     * Delete a category sale.
     */
    public function destroyCategorySale($id): JsonResponse
    {
        $sale = PricingCategorySale::findOrFail($id);
        $this->logActivity('sale_deleted', "Category sale \"{$sale->name}\" was deleted.");
        $sale->delete();

        // Category sale removed — sync all plans with updated effective prices
        $this->syncAllPlansToSquare();

        return response()->json([
            'message' => 'Category sale deleted successfully.',
        ]);
    }

    /**
     * Sync a plan's prices to Square catalog using effective prices (after discounts).
     */
    private function syncPlanToSquare(PricingPlan $plan): void
    {
        try {
            $squareService = new SquareCatalogService();

            foreach ($plan->prices as $price) {
                if ($price->price > 0) {
                    // Use effective price (after plan-level and category-level discounts)
                    $effective = $price->getEffectivePrice();
                    $syncPrice = (float) $effective['final_price'];

                    $variationId = $squareService->syncPlanVariation(
                        $plan->name,
                        $price->billing_period,
                        $syncPrice,
                        $price->currency ?? '$',
                        $price->square_plan_variation_id
                    );

                    if ($variationId && $variationId !== $price->square_plan_variation_id) {
                        $price->square_plan_variation_id = $variationId;
                        $price->save();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Square catalog sync failed for plan {$plan->name}: " . $e->getMessage());
        }
    }

    /**
     * Manually sync all pricing plans to Square catalog.
     * Uses effective prices (after plan-level and category-level discounts).
     */
    public function syncToSquare(): JsonResponse
    {
        $plans = PricingPlan::active()->with('prices')->get();
        $squareService = new SquareCatalogService();

        $results = [];
        $errors = [];

        foreach ($plans as $plan) {
            foreach ($plan->prices as $price) {
                if ($price->price <= 0) continue;

                $effective = $price->getEffectivePrice();
                $syncPrice = (float) $effective['final_price'];
                $label = "{$plan->name} - " . ucfirst($price->billing_period);

                try {
                    $variationId = $squareService->syncPlanVariation(
                        $plan->name,
                        $price->billing_period,
                        $syncPrice,
                        $price->currency ?? '$',
                        $price->square_plan_variation_id
                    );

                    if ($variationId) {
                        if ($variationId !== $price->square_plan_variation_id) {
                            $price->square_plan_variation_id = $variationId;
                            $price->save();
                        }

                        $results[] = [
                            'plan' => $label,
                            'price' => $syncPrice,
                            'on_sale' => $effective['on_sale'],
                            'original_price' => $effective['on_sale'] ? (float) $effective['original_price'] : null,
                            'variation_id' => $variationId,
                            'status' => 'synced',
                        ];
                    } else {
                        $errors[] = "{$label}: sync returned null";
                    }
                } catch (\Exception $e) {
                    $errors[] = "{$label}: {$e->getMessage()}";
                }
            }
        }

        $this->logActivity('square_sync', 'Pricing plans synced to Square catalog. ' . count($results) . ' synced, ' . count($errors) . ' errors.');

        return response()->json([
            'message' => count($errors) === 0
                ? 'All plans synced to Square successfully.'
                : count($results) . ' plans synced, ' . count($errors) . ' failed.',
            'synced' => $results,
            'errors' => $errors,
        ]);
    }

    /**
     * Silently sync all active plans to Square (used by category sale triggers).
     * Non-blocking — logs errors but doesn't fail the parent operation.
     */
    private function syncAllPlansToSquare(): void
    {
        try {
            $plans = PricingPlan::active()->with('prices')->get();
            foreach ($plans as $plan) {
                $this->syncPlanToSquare($plan);
            }
            Log::info('All pricing plans synced to Square after category sale change.');
        } catch (\Exception $e) {
            Log::warning('Failed to sync plans to Square after category sale change: ' . $e->getMessage());
        }
    }
}
