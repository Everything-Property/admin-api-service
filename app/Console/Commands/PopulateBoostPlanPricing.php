<?php

namespace App\Console\Commands;

use App\Models\BoostPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PopulateBoostPlanPricing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'boost:pricing:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate boost plan pricing table based on existing boost plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate boost plan pricing...');

        // Clear existing pricing data
        DB::table('boost_plan_pricing')->delete();
        $this->info('Cleared existing pricing data.');

        $now = Carbon::now();
        $accountTypes = ['ROLE_BROKER', 'ROLE_COMPANY', 'ROLE_DEVELOPER'];
        $plans = BoostPlan::all();

        $this->info("Found {$plans->count()} boost plans to process.");

        foreach ($plans as $plan) {
            $this->info("Processing plan: {$plan->name} (ID: {$plan->id})");

            foreach ($accountTypes as $accountType) {
                $basePrice = (float) $plan->base_price;
                $quarterlyDiscount = (float) $plan->quarterly_discount / 100;
                $yearlyDiscount = (float) $plan->yearly_discount / 100;

                $monthlyPrice = $basePrice;
                $quarterlyPrice = ($basePrice * 3) * (1 - $quarterlyDiscount);
                $yearlyPrice = ($basePrice * 12) * (1 - $yearlyDiscount);

                DB::table('boost_plan_pricing')->insert([
                    'boost_plan_id' => $plan->id,
                    'account_type' => $accountType,
                    'monthly_price' => $monthlyPrice,
                    'quarterly_price' => $quarterlyPrice,
                    'yearly_price' => $yearlyPrice,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->line("  - Created pricing for {$accountType}: Monthly: ₦{$monthlyPrice}, Quarterly: ₦{$quarterlyPrice}, Yearly: ₦{$yearlyPrice}");
            }
        }

        $totalPricing = DB::table('boost_plan_pricing')->count();
        $this->info("Successfully created {$totalPricing} pricing records!");

        return 0;
    }
}
