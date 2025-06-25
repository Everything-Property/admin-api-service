<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BoostPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Clear existing data
        DB::table('boost_plan_pricing')->delete();
        DB::table('boost_plans')->delete();

        // Create broker plans
        $this->createBrokerPlans($now);
        
        // Create company plans
        $this->createCompanyPlans($now);
        
        // Create developer plans
        $this->createDeveloperPlans($now);
    }

    /**
     * Create broker boost plans
     */
    private function createBrokerPlans(Carbon $now): void
    {
        $brokerPlans = [
            [
                'name' => 'Free',
                'description' => 'Starter Pack - Basic listing capabilities for new brokers',
                'listing_limit' => 50,
                'base_price' => 0.00,
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 1,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 1',
                'description' => 'Beginner - Enhanced listing range for growing brokers',
                'listing_limit' => 100,
                'base_price' => 15000.00, // ₦15,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 5,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 2',
                'description' => 'Apprentice plan - Expanded capabilities for active brokers',
                'listing_limit' => 150,
                'base_price' => 25000.00, // ₦25,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 10,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 3',
                'description' => 'Advance - Professional tier for established brokers',
                'listing_limit' => 200,
                'base_price' => 35000.00, // ₦35,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 15,
                'is_active' => true,
                'is_recommended' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 4',
                'description' => 'Expert - High-volume listing capabilities',
                'listing_limit' => 250,
                'base_price' => 45000.00, // ₦45,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 20,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 5',
                'description' => 'Biggie Pro - Premium tier for top-performing brokers',
                'listing_limit' => 300,
                'base_price' => 55000.00, // ₦55,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 25,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 6',
                'description' => 'Biggie max - Advanced tier for high-volume brokers',
                'listing_limit' => 350,
                'base_price' => 65000.00, // ₦65,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 30,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 16,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 7',
                'description' => 'Max plan - Professional tier for expert brokers',
                'listing_limit' => 400,
                'base_price' => 75000.00, // ₦75,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 35,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 17,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 8',
                'description' => 'Max pro - Elite tier for top brokers',
                'listing_limit' => 450,
                'base_price' => 85000.00, // ₦85,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 40,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 18,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 9',
                'description' => 'Max ultimate - Premium tier for market leaders',
                'listing_limit' => 500,
                'base_price' => 95000.00, // ₦95,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 45,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 19,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 10',
                'description' => 'Exotic - Exclusive tier for premium brokers',
                'listing_limit' => 550,
                'base_price' => 100000.00, // ₦100,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 50,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 11',
                'description' => 'Exotic pro - Advanced exclusive tier',
                'listing_limit' => 1000,
                'base_price' => 150000.00, // ₦150,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 55,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 12,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 12',
                'description' => 'Exotic max - Maximum exclusive capabilities',
                'listing_limit' => 2000,
                'base_price' => 200000.00, // ₦200,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 60,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 13,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 13',
                'description' => 'Ultimate Plan - Top-tier professional package',
                'listing_limit' => 3000,
                'base_price' => 300000.00, // ₦300,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 65,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 14,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Plan 14',
                'description' => 'Ultimate Pro - Premium unlimited package',
                'listing_limit' => 5000,
                'base_price' => 400000.00, // ₦400,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => -1, // Unlimited
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 15,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($brokerPlans as $plan) {
            $planId = DB::table('boost_plans')->insertGetId($plan);
            
            // Create pricing for ROLE_BROKER
            $basePrice = $plan['base_price'];
            $quarterlyDiscount = $plan['quarterly_discount'] / 100;
            $yearlyDiscount = $plan['yearly_discount'] / 100;
            
            $monthlyPrice = $basePrice;
            $quarterlyPrice = ($basePrice * 3) * (1 - $quarterlyDiscount);
            $yearlyPrice = ($basePrice * 12) * (1 - $yearlyDiscount);
            
            DB::table('boost_plan_pricing')->insert([
                'boost_plan_id' => $planId,
                'account_type' => 'ROLE_BROKER',
                'monthly_price' => $monthlyPrice,
                'quarterly_price' => $quarterlyPrice,
                'yearly_price' => $yearlyPrice,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Create company boost plans
     */
    private function createCompanyPlans(Carbon $now): void
    {
        $companyPlans = [
            [
                'name' => 'Free',
                'description' => 'Basic company listing package',
                'listing_limit' => 10,
                'base_price' => 0.00,
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 1,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 16,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Company Plan 1',
                'description' => 'Small business package for growing companies',
                'listing_limit' => 100,
                'base_price' => 30000.00, // ₦30,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 5,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 17,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Company Plan 2',
                'description' => 'Medium business package for established companies',
                'listing_limit' => 300,
                'base_price' => 50000.00, // ₦50,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 10,
                'is_active' => true,
                'is_recommended' => true,
                'sort_order' => 18,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Company Plan 3',
                'description' => 'Large business package for enterprise companies',
                'listing_limit' => 500,
                'base_price' => 150000.00, // ₦150,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 40,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 19,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Company Plan 4',
                'description' => 'Enterprise package with unlimited capabilities',
                'listing_limit' => 1500,
                'base_price' => 500000.00, // ₦500,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => -1, // Unlimited
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($companyPlans as $plan) {
            $planId = DB::table('boost_plans')->insertGetId($plan);
            
            // Create pricing for ROLE_COMPANY
            $basePrice = $plan['base_price'];
            $quarterlyDiscount = $plan['quarterly_discount'] / 100;
            $yearlyDiscount = $plan['yearly_discount'] / 100;
            
            $monthlyPrice = $basePrice;
            $quarterlyPrice = ($basePrice * 3) * (1 - $quarterlyDiscount);
            $yearlyPrice = ($basePrice * 12) * (1 - $yearlyDiscount);
            
            DB::table('boost_plan_pricing')->insert([
                'boost_plan_id' => $planId,
                'account_type' => 'ROLE_COMPANY',
                'monthly_price' => $monthlyPrice,
                'quarterly_price' => $quarterlyPrice,
                'yearly_price' => $yearlyPrice,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Create developer boost plans
     */
    private function createDeveloperPlans(Carbon $now): void
    {
        $developerPlans = [
            [
                'name' => 'Free',
                'description' => 'Basic developer package for small projects',
                'listing_limit' => 2,
                'base_price' => 0.00,
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 0,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 21,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Developer Plan 1',
                'description' => 'Area/District analytics for local developers',
                'listing_limit' => 100,
                'base_price' => 150000.00, // ₦150,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 0,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 22,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Developer Plan 2',
                'description' => 'State analytics for regional developers',
                'listing_limit' => 300,
                'base_price' => 250000.00, // ₦250,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 0,
                'is_active' => true,
                'is_recommended' => true,
                'sort_order' => 23,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Developer Plan 3',
                'description' => 'Regional analytics for multi-state developers',
                'listing_limit' => 500,
                'base_price' => 450000.00, // ₦450,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 0,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 24,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Developer Plan 4',
                'description' => 'National analytics for nationwide developers',
                'listing_limit' => 1500,
                'base_price' => 1000000.00, // ₦1,000,000
                'quarterly_discount' => 5.00,
                'yearly_discount' => 5.00,
                'free_viewing_requests_per_month' => 0,
                'is_active' => true,
                'is_recommended' => false,
                'sort_order' => 25,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($developerPlans as $plan) {
            $planId = DB::table('boost_plans')->insertGetId($plan);
            
            // Create pricing for ROLE_DEVELOPER
            $basePrice = $plan['base_price'];
            $quarterlyDiscount = $plan['quarterly_discount'] / 100;
            $yearlyDiscount = $plan['yearly_discount'] / 100;
            
            $monthlyPrice = $basePrice;
            $quarterlyPrice = ($basePrice * 3) * (1 - $quarterlyDiscount);
            $yearlyPrice = ($basePrice * 12) * (1 - $yearlyDiscount);
            
            DB::table('boost_plan_pricing')->insert([
                'boost_plan_id' => $planId,
                'account_type' => 'ROLE_DEVELOPER',
                'monthly_price' => $monthlyPrice,
                'quarterly_price' => $quarterlyPrice,
                'yearly_price' => $yearlyPrice,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}