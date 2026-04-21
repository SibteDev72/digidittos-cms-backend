<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SquareCatalogService
{
    private string $baseUrl;
    private string $accessToken;

    public function __construct()
    {
        $environment = env('SQUARE_ENVIRONMENT', 'sandbox');
        $this->baseUrl = $environment === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';
        $this->accessToken = env('SQUARE_ACCESS_TOKEN', '');
    }

    /**
     * Create or update a subscription plan variation in Square's catalog.
     * Returns the Square plan variation ID.
     */
    public function syncPlanVariation(string $planName, string $billingPeriod, float $price, string $currency = 'USD', ?string $existingVariationId = null): ?string
    {
        if (empty($this->accessToken)) {
            Log::warning('Square access token not configured — skipping catalog sync');
            return null;
        }

        // Map currency symbols to ISO codes
        $currencyMap = ['$' => 'USD', '€' => 'EUR', '£' => 'GBP', '₹' => 'INR', '¥' => 'JPY'];
        $isoCurrency = $currencyMap[$currency] ?? strtoupper($currency);

        $amountInCents = (int) round($price * 100);
        $cadence = strtoupper($billingPeriod) === 'ANNUAL' ? 'ANNUAL' : 'MONTHLY';
        $variationName = "{$planName} - " . ucfirst(strtolower($billingPeriod));

        // If we have an existing variation ID, update it. Otherwise create new.
        if ($existingVariationId) {
            return $this->updateVariation($existingVariationId, $variationName, $cadence, $amountInCents, $isoCurrency);
        }

        return $this->createVariation($planName, $variationName, $cadence, $amountInCents, $isoCurrency);
    }

    private function createVariation(string $planName, string $variationName, string $cadence, int $amountInCents, string $currency): ?string
    {
        try {
            // Create a subscription plan with one variation via batch upsert
            $planTempId = '#plan_' . Str::slug($planName) . '_' . strtolower($cadence);
            $variationTempId = '#var_' . Str::slug($planName) . '_' . strtolower($cadence);

            $response = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/v2/catalog/batch-upsert", [
                    'idempotency_key' => Str::uuid()->toString(),
                    'batches' => [
                        [
                            'objects' => [
                                [
                                    'type' => 'SUBSCRIPTION_PLAN',
                                    'id' => $planTempId,
                                    'subscription_plan_data' => [
                                        'name' => $variationName . ' Plan',
                                        'phases' => [
                                            [
                                                'cadence' => $cadence,
                                                'pricing' => [
                                                    'type' => 'STATIC',
                                                    'price_money' => [
                                                        'amount' => $amountInCents,
                                                        'currency' => $currency,
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'subscription_plan_variations' => [
                                            [
                                                'type' => 'SUBSCRIPTION_PLAN_VARIATION',
                                                'id' => $variationTempId,
                                                'subscription_plan_variation_data' => [
                                                    'name' => $variationName,
                                                    'phases' => [
                                                        [
                                                            'cadence' => $cadence,
                                                            'pricing' => [
                                                                'type' => 'STATIC',
                                                                'price_money' => [
                                                                    'amount' => $amountInCents,
                                                                    'currency' => $currency,
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $mappings = $response->json('id_mappings') ?? [];
                foreach ($mappings as $mapping) {
                    if ($mapping['client_object_id'] === $variationTempId) {
                        $variationId = $mapping['object_id'];
                        Log::info("Square catalog plan variation created: {$variationName} => {$variationId}");
                        return $variationId;
                    }
                }
            }

            Log::error('Square catalog sync failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Square catalog sync error: ' . $e->getMessage());
            return null;
        }
    }

    private function updateVariation(string $variationId, string $variationName, string $cadence, int $amountInCents, string $currency): ?string
    {
        try {
            // Retrieve the current object to get its version
            $getResponse = Http::withToken($this->accessToken)
                ->get("{$this->baseUrl}/v2/catalog/object/{$variationId}");

            if (!$getResponse->successful()) {
                Log::warning("Square catalog object not found: {$variationId}, creating new");
                return $this->createVariation($variationName, $variationName, $cadence, $amountInCents, $currency);
            }

            $existingObject = $getResponse->json('object');
            $version = $existingObject['version'] ?? null;

            $response = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/v2/catalog/batch-upsert", [
                    'idempotency_key' => Str::uuid()->toString(),
                    'batches' => [
                        [
                            'objects' => [
                                [
                                    'type' => 'SUBSCRIPTION_PLAN_VARIATION',
                                    'id' => $variationId,
                                    'version' => $version,
                                    'subscription_plan_variation_data' => [
                                        'name' => $variationName,
                                        'phases' => [
                                            [
                                                'cadence' => $cadence,
                                                'pricing' => [
                                                    'type' => 'STATIC',
                                                    'price_money' => [
                                                        'amount' => $amountInCents,
                                                        'currency' => $currency,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                Log::info("Square catalog plan variation updated: {$variationName} => {$variationId}");
                return $variationId;
            }

            Log::error('Square catalog update failed: ' . $response->body());
            return $variationId; // Return existing ID even if update failed
        } catch (\Exception $e) {
            Log::error('Square catalog update error: ' . $e->getMessage());
            return $variationId;
        }
    }
}
