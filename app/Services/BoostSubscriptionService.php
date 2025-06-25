<?php

namespace App\Services;

use App\Models\BoostPlan;
use App\Models\User;
use App\Models\UserBoostSubscription;
use App\Models\UserViewingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class BoostSubscriptionService
{
    private FlutterwaveService $flutterwaveService;

    public function __construct(FlutterwaveService $flutterwaveService)
    {
        $this->flutterwaveService = $flutterwaveService;
    }

    /**
     * Get available boost plans for a user's account type
     */
    public function getAvailablePlans(?User $user = null, ?string $userRole = null): array
    {
        // If no userRole is provided, return all plans grouped by role
        if (!$userRole) {
            return $this->getAllPlansGroupedByRole();
        }

        // Priority: explicit role parameter > authenticated user's role > default to broker
        if ($userRole && in_array($userRole, ['broker', 'company', 'developer'])) {
            $userAccountType = 'ROLE_' . strtoupper($userRole);
        } elseif ($user) {
            $userAccountType = $this->getUserAccountType($user);
        } else {
            $userAccountType = 'ROLE_BROKER'; // Default to broker for public access
        }

        $plans = BoostPlan::active()
            ->ordered()
            ->whereHas('pricing', function ($query) use ($userAccountType) {
                $query->where('account_type', $userAccountType);
            })
            ->with(['pricing' => function ($query) use ($userAccountType) {
                $query->where('account_type', $userAccountType);
            }])
            ->get();

        return $plans->map(function ($plan) use ($userAccountType) {
            // Get pricing from the relationship
            $pricing = $plan->pricing->where('account_type', $userAccountType)->first();

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'listing_limit' => $plan->listing_limit,
                'free_viewing_requests_per_month' => $plan->free_viewing_requests_per_month,
                'is_recommended' => $plan->is_recommended,
                'pricing' => [
                    'monthly' => $pricing ? $pricing->monthly_price : $plan->base_price,
                    'quarterly' => $pricing ? $pricing->quarterly_price : ($plan->base_price * 3 * (1 - $plan->quarterly_discount / 100)),
                    'yearly' => $pricing ? $pricing->yearly_price : ($plan->base_price * 12 * (1 - $plan->yearly_discount / 100)),
                ],
                'discounts' => [
                    'quarterly' => $plan->quarterly_discount,
                    'yearly' => $plan->yearly_discount,
                ]
            ];
        })->toArray();
    }

    /**
     * Get all available boost plans grouped by account type
     */
    private function getAllPlansGroupedByRole(): array
    {
        $accountTypes = ['ROLE_BROKER', 'ROLE_COMPANY', 'ROLE_DEVELOPER'];
        $result = [];

        foreach ($accountTypes as $accountType) {
            $plans = BoostPlan::active()
                ->ordered()
                ->whereHas('pricing', function ($query) use ($accountType) {
                    $query->where('account_type', $accountType);
                })
                ->with(['pricing' => function ($query) use ($accountType) {
                    $query->where('account_type', $accountType);
                }])
                ->get();

            $roleKey = strtolower(str_replace('ROLE_', '', $accountType));

            $result[$roleKey] = $plans->map(function ($plan) use ($accountType) {
                $pricing = $plan->pricing->where('account_type', $accountType)->first();

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'listing_limit' => $plan->listing_limit,
                    'free_viewing_requests_per_month' => $plan->free_viewing_requests_per_month,
                    'is_recommended' => $plan->is_recommended,
                    'pricing' => [
                        'monthly' => $pricing ? $pricing->monthly_price : $plan->base_price,
                        'quarterly' => $pricing ? $pricing->quarterly_price : ($plan->base_price * 3 * (1 - $plan->quarterly_discount / 100)),
                        'yearly' => $pricing ? $pricing->yearly_price : ($plan->base_price * 12 * (1 - $plan->yearly_discount / 100)),
                    ],
                    'discounts' => [
                        'quarterly' => $plan->quarterly_discount,
                        'yearly' => $plan->yearly_discount,
                    ]
                ];
            })->toArray();
        }

        return $result;
    }

    /**
     * Get user's current active boost subscription
     */
    public function getCurrentSubscription(User $user): ?UserBoostSubscription
    {
        return UserBoostSubscription::forUser($user->id)
            ->active()
            ->with('boostPlan')
            ->first();
    }

    /**
     * Initialize payment for boost subscription
     */
    public function initializeSubscriptionPayment(User $user, int $boostPlanId, string $billingCycle, string $redirectUrl): array
    {
        try {
            DB::beginTransaction();

            $boostPlan = BoostPlan::findOrFail($boostPlanId);
            $userAccountType = $this->getUserAccountType($user);

            // Get pricing from the relationship
            $pricing = $boostPlan->pricing()->where('account_type', $userAccountType)->first();

            // Calculate pricing
            $basePrice = $pricing ? $pricing->monthly_price : $boostPlan->base_price;

            switch ($billingCycle) {
                case 'quarterly':
                    $amount = $pricing ? $pricing->quarterly_price : ($boostPlan->base_price * 3 * (1 - $boostPlan->quarterly_discount / 100));
                    break;
                case 'yearly':
                    $amount = $pricing ? $pricing->yearly_price : ($boostPlan->base_price * 12 * (1 - $boostPlan->yearly_discount / 100));
                    break;
                default: // monthly
                    $amount = $basePrice;
                    break;
            }

            // Calculate discount applied
            $discountApplied = 0;
            if ($billingCycle === 'quarterly') {
                $discountApplied = $boostPlan->quarterly_discount;
            } elseif ($billingCycle === 'yearly') {
                $discountApplied = $boostPlan->yearly_discount;
            }

            // Generate transaction reference
            $txRef = $this->flutterwaveService->generateTransactionReference('BOOST_SUB');

            // Calculate subscription dates
            $startDate = now();
            $endDate = $this->calculateEndDate($startDate, $billingCycle);

            // Create pending subscription record
            $subscription = UserBoostSubscription::create([
                'user_id' => $user->id,
                'boost_plan_id' => $boostPlan->id,
                'billing_cycle' => $billingCycle,
                'amount_paid' => $amount,
                'discount_applied' => $discountApplied,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'auto_renew' => false,
                'status' => 'pending',
                'flutterwave_transaction_id' => $txRef,
            ]);

            // Initialize Flutterwave payment
            $paymentData = [
                'tx_ref' => $txRef,
                'amount' => $amount,
                'currency' => 'NGN',
                'redirect_url' => $redirectUrl,
                'customer' => [
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ],
                'title' => 'Boost Subscription - ' . $boostPlan->name,
                'description' => "Boost subscription for {$boostPlan->name} plan ({$billingCycle})",
                'meta' => [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'boost_plan_id' => $boostPlan->id,
                    'billing_cycle' => $billingCycle,
                ]
            ];

            $paymentResponse = $this->flutterwaveService->initializePayment($paymentData);

            if ($paymentResponse['status'] === 'success') {
                DB::commit();
                return [
                    'status' => 'success',
                    'subscription_id' => $subscription->id,
                    'payment_link' => $paymentResponse['data']['link'],
                    'transaction_id' => $txRef,
                    'amount' => $amount,
                ];
            } else {
                DB::rollBack();
                return $paymentResponse;
            }
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => 'Failed to initialize subscription payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify and activate subscription after payment
     */
    public function verifyAndActivateSubscription(string $transactionId): array
    {
        try {
            DB::beginTransaction();

            $subscription = UserBoostSubscription::where('flutterwave_transaction_id', $transactionId)
                ->where('status', 'pending')
                ->first();

            if (!$subscription) {
                return [
                    'status' => 'error',
                    'message' => 'Subscription not found or already processed'
                ];
            }

            // Verify payment with Flutterwave
            $verificationResponse = $this->flutterwaveService->verifyPayment($transactionId);

            if ($verificationResponse['status'] === 'success' && $verificationResponse['is_successful']) {
                // Deactivate any existing active subscriptions for this user
                UserBoostSubscription::forUser($subscription->user_id)
                    ->active()
                    ->update(['status' => 'cancelled']);

                // Activate the new subscription
                $subscription->update([
                    'status' => 'active',
                    'flutterwave_response' => $verificationResponse['data']
                ]);

                DB::commit();

                return [
                    'status' => 'success',
                    'message' => 'Subscription activated successfully',
                    'subscription' => $subscription->load('boostPlan')
                ];
            } else {
                $subscription->update([
                    'status' => 'cancelled',
                    'flutterwave_response' => $verificationResponse['data'] ?? null
                ]);

                DB::commit();

                return [
                    'status' => 'error',
                    'message' => 'Payment verification failed'
                ];
            }
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => 'Failed to verify subscription: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if user can make a free viewing request
     */
    public function canMakeFreeViewingRequest(User $user): array
    {
        $currentSubscription = $this->getCurrentSubscription($user);

        if (!$currentSubscription) {
            return [
                'can_request' => false,
                'reason' => 'No active boost subscription',
                'free_requests_used' => 0,
                'free_requests_limit' => 0
            ];
        }

        $currentMonth = now()->month;
        $currentYear = now()->year;

        $freeRequestsUsed = UserViewingRequest::getFreeRequestsCountForMonth(
            $user->id,
            $currentYear,
            $currentMonth
        );

        $freeRequestsLimit = $currentSubscription->boostPlan->free_viewing_requests_per_month;

        return [
            'can_request' => $freeRequestsUsed < $freeRequestsLimit,
            'reason' => $freeRequestsUsed >= $freeRequestsLimit ? 'Monthly free quota exceeded' : null,
            'free_requests_used' => $freeRequestsUsed,
            'free_requests_limit' => $freeRequestsLimit
        ];
    }

    /**
     * Create a viewing request
     */
    public function createViewingRequest(User $user, int $propertyId, bool $forcePaid = false): array
    {
        try {
            $canMakeFreeRequest = $this->canMakeFreeViewingRequest($user);

            $requestType = 'paid';
            $amountCharged = 0;

            if (!$forcePaid && $canMakeFreeRequest['can_request']) {
                $requestType = 'free';
            } else {
                // Calculate paid viewing request cost (you can customize this)
                $amountCharged = 5000; // 50 NGN for example
            }

            $viewingRequest = UserViewingRequest::create([
                'user_id' => $user->id,
                'property_id' => $propertyId,
                'request_type' => $requestType,
                'amount_charged' => $amountCharged,
                'year' => now()->year,
                'month' => now()->month,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            return [
                'status' => 'success',
                'viewing_request' => $viewingRequest,
                'request_type' => $requestType,
                'amount_charged' => $amountCharged
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to create viewing request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's account type from roles
     * Handles role combinations like:
     * - Brokers: ["ROLE_USER","ROLE_BROKERAGE","ROLE_BROKER"]
     * - Companies: ["ROLE_USER", "ROLE_COMPANY"]
     * - Developers: ["ROLE_USER","ROLE_DEVELOPER"]
     */
    private function getUserAccountType(User $user): string
    {
        $roles = $user->roles ?? [];

        // Priority order for account types - check most specific roles first
        // For brokers, check both ROLE_BROKER and ROLE_BROKERAGE
        if (in_array('ROLE_BROKER', $roles) || in_array('ROLE_BROKERAGE', $roles)) {
            return 'ROLE_BROKER';
        }

        if (in_array('ROLE_DEVELOPER', $roles)) {
            return 'ROLE_DEVELOPER';
        }

        if (in_array('ROLE_COMPANY', $roles)) {
            return 'ROLE_COMPANY';
        }

        // Default to user if only ROLE_USER or no specific roles found
        return 'ROLE_USER';
    }

    /**
     * Calculate subscription end date based on billing cycle
     */
    private function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        switch ($billingCycle) {
            case 'quarterly':
                return $startDate->copy()->addMonths(3);
            case 'yearly':
                return $startDate->copy()->addYear();
            default: // monthly
                return $startDate->copy()->addMonth();
        }
    }
}
