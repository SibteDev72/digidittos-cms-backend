<?php

namespace Database\Seeders;

use App\Models\PricingCategorySale;
use App\Models\PricingPlan;
use App\Models\PricingPlanPrice;
use App\Models\PricingSetting;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        // Plans
        $starter = PricingPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Perfect for small practices getting started',
                'custom_price_label' => null,
                'is_highlighted' => false,
                'highlight_label' => null,
                'cta_text' => 'Start Free Trial',
                'cta_url' => null,
                'features' => [
                    'Up to 5 users',
                    'Up to 100 active clients',
                    'Electronic Health Records',
                    'Basic document templates',
                    'Client management',
                    'Email support',
                    'Compliant secure storage',
                    'Mobile access',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        PricingPlanPrice::updateOrCreate(
            ['pricing_plan_id' => $starter->id, 'billing_period' => 'monthly'],
            ['price' => 99, 'currency' => '$']
        );
        PricingPlanPrice::updateOrCreate(
            ['pricing_plan_id' => $starter->id, 'billing_period' => 'annual'],
            ['price' => 79, 'currency' => '$']
        );

        $professional = PricingPlan::updateOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'description' => 'For growing organizations that need more',
                'custom_price_label' => null,
                'is_highlighted' => true,
                'highlight_label' => 'MOST POPULAR',
                'cta_text' => 'Start Free Trial',
                'cta_url' => null,
                'features' => [
                    'Up to 25 users',
                    'Up to 500 active clients',
                    'Everything in Starter, plus:',
                    'Form Factory builder',
                    'Document workflows',
                    'Service authorizations',
                    'Timesheet management',
                    'Custom forms & templates',
                    'Priority email support',
                    'API access',
                    'Advanced reporting',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        PricingPlanPrice::updateOrCreate(
            ['pricing_plan_id' => $professional->id, 'billing_period' => 'monthly'],
            ['price' => 249, 'currency' => '$']
        );
        PricingPlanPrice::updateOrCreate(
            ['pricing_plan_id' => $professional->id, 'billing_period' => 'annual'],
            ['price' => 199, 'currency' => '$']
        );

        $enterprise = PricingPlan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'For large organizations with custom needs',
                'custom_price_label' => 'Custom',
                'is_highlighted' => false,
                'highlight_label' => null,
                'cta_text' => 'Contact Sales',
                'cta_url' => null,
                'features' => [
                    'Unlimited users',
                    'Unlimited clients',
                    'Everything in Professional, plus:',
                    'Dedicated database instance',
                    'Custom integrations',
                    'Clearing house integration',
                    'Advanced analytics',
                    'Two-factor authentication',
                    'SSO integration',
                    'Dedicated account manager',
                    '24/7 phone support',
                    'Custom training',
                    'SLA guarantee',
                ],
                'sort_order' => 3,
                'is_active' => true,
            ]
        );

        // Category Sales
        PricingCategorySale::updateOrCreate(
            ['billing_period' => 'annual', 'name' => 'Annual Discount'],
            ['discount_percentage' => 20, 'label' => 'Save 20%', 'is_active' => true, 'priority' => 1]
        );

        // Settings — Hero
        PricingSetting::setValue('pricing_hero_label', 'PRICING', 'string');
        PricingSetting::setValue('pricing_hero_headline', ['Simple,', 'transparent', 'pricing'], 'json');
        PricingSetting::setValue('pricing_hero_subtitle', 'Choose the plan that fits your organization. All plans include enterprise-grade security, regular updates, and access to our support team.', 'string');

        // Settings — Comparison Features
        PricingSetting::setValue('pricing_comparison_label', 'COMPARE PLANS', 'string');
        PricingSetting::setValue('pricing_comparison_headline', 'All Plans Include', 'string');
        PricingSetting::setValue('pricing_comparison_features', [
            ['title' => 'HIPAA Compliance', 'description' => 'All data is encrypted at rest and in transit. Comprehensive audit logging and access controls.'],
            ['title' => 'Regular Updates', 'description' => 'Continuous improvements and new features rolled out automatically at no extra cost.'],
            ['title' => 'Data Backups', 'description' => 'Automated daily backups with point-in-time recovery. Your data is always safe.'],
            ['title' => 'Mobile Access', 'description' => 'Access DigiDittos from any device with our responsive web application.'],
            ['title' => 'Secure Authentication', 'description' => 'Strong password policies, session management, and optional two-factor authentication.'],
            ['title' => 'Onboarding Support', 'description' => 'Guided setup, data migration assistance, and training resources for your team.'],
        ], 'json');

        // Settings — CTA
        PricingSetting::setValue('pricing_cta_headline', 'Ready to get started?', 'string');
        PricingSetting::setValue('pricing_cta_subtitle', 'Start your 14-day free trial today. No credit card required.', 'string');
        PricingSetting::setValue('pricing_cta_primary_text', 'Start Free Trial', 'string');
        PricingSetting::setValue('pricing_cta_primary_url', '/#contact', 'string');
        PricingSetting::setValue('pricing_cta_secondary_text', 'Contact Sales', 'string');
        PricingSetting::setValue('pricing_cta_secondary_url', '/#contact', 'string');
    }
}
