<?php

namespace App\Services;

use App\Models\BoostPlan;
use App\Models\User;
use App\Models\UserBoostSubscription;
use App\Models\UserViewingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'listing_limit' => $plan->listing_limit,
                'free_viewing_requests_per_month' => $plan->free_viewing_requests_per_month,
                'is_recommended' => $plan->is_recommended,
                'pricing' => [
                    'monthly' => $plan->getPriceForAccountType($userAccountType, 'monthly'),
                    'quarterly' => $plan->getPriceForAccountType($userAccountType, 'quarterly'),
                    'yearly' => $plan->getPriceForAccountType($userAccountType, 'yearly'),
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
    public function getAllPlansGroupedByRole(): array
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
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'listing_limit' => $plan->listing_limit,
                    'free_viewing_requests_per_month' => $plan->free_viewing_requests_per_month,
                    'is_recommended' => $plan->is_recommended,
                    'pricing' => [
                        'monthly' => $plan->getPriceForAccountType($accountType, 'monthly'),
                        'quarterly' => $plan->getPriceForAccountType($accountType, 'quarterly'),
                        'yearly' => $plan->getPriceForAccountType($accountType, 'yearly'),
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

            // Debug information
            Log::info('Boost subscription payment initialization', [
                'user_id' => $user->id,
                'boost_plan_id' => $boostPlanId,
                'billing_cycle' => $billingCycle,
                'user_account_type' => $userAccountType,
                'boost_plan_name' => $boostPlan->name,
                'boost_plan_base_price' => $boostPlan->base_price,
                'user_roles' => $user->roles,
            ]);

            // Use the BoostPlan's built-in pricing method
            $amount = $boostPlan->getPriceForAccountType($userAccountType, $billingCycle);

            // Debug the calculated amount
            Log::info('Calculated amount', [
                'amount' => $amount,
                'account_type' => $userAccountType,
                'billing_cycle' => $billingCycle,
            ]);

            // Check if this is a free plan
            if ($amount == 0) {
                return [
                    'status' => 'error',
                    'message' => 'Cannot subscribe to a free plan. Please select a paid plan.'
                ];
            }

            // Validate calculated amount
            if (!$amount || $amount <= 0) {
                Log::error('Invalid amount calculated', [
                    'amount' => $amount,
                    'user_account_type' => $userAccountType,
                    'billing_cycle' => $billingCycle,
                    'boost_plan_id' => $boostPlanId,
                ]);

                return [
                    'status' => 'error',
                    'message' => 'Invalid amount calculated for billing cycle. Please contact support.'
                ];
            }

            // Ensure amount is at least 1 (Flutterwave requirement)
            $amount = max(1, $amount);

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
            Log::error('Boost subscription payment initialization error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'status' => 'error',
                'message' => 'Failed to initialize subscription payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify and activate subscription after payment
     */
    public function verifyAndActivateSubscription(string $transactionId, ?User $user = null): array
    {
        try {
            DB::beginTransaction();

            // Find the subscription by transaction reference
            $subscription = UserBoostSubscription::where('flutterwave_transaction_id', $transactionId)
                ->where('status', 'pending')
                ->first();

            if (!$subscription) {
                return [
                    'status' => 'error',
                    'message' => 'Subscription not found or already processed'
                ];
            }

            // If user is provided, verify it matches the subscription
            if ($user && $subscription->user_id !== $user->id) {
                return [
                    'status' => 'error',
                    'message' => 'This transaction does not belong to you'
                ];
            }

            // Verify payment with Flutterwave
            $verificationResponse = $this->flutterwaveService->verifyPayment($transactionId);

            if ($verificationResponse['status'] === 'success' && $verificationResponse['is_successful']) {
                $flutterwaveData = $verificationResponse['data'];

                // Additional security checks
                $expectedAmount = $subscription->amount_paid;
                $actualAmount = (float) $flutterwaveData['amount'];

                // Verify amount matches (with small tolerance for currency conversion)
                if (abs($actualAmount - $expectedAmount) > 1) {
                    Log::warning('Amount mismatch in payment verification', [
                        'transaction_id' => $transactionId,
                        'expected_amount' => $expectedAmount,
                        'actual_amount' => $actualAmount,
                        'subscription_id' => $subscription->id
                    ]);

                    return [
                        'status' => 'error',
                        'message' => 'Payment amount mismatch detected'
                    ];
                }

                // Verify currency
                if ($flutterwaveData['currency'] !== 'NGN') {
                    return [
                        'status' => 'error',
                        'message' => 'Invalid currency detected'
                    ];
                }

                // Deactivate any existing active subscriptions for this user
                UserBoostSubscription::forUser($subscription->user_id)
                    ->active()
                    ->update(['status' => 'cancelled']);

                // Activate the new subscription
                $subscription->update([
                    'status' => 'active',
                    'flutterwave_response' => json_encode($verificationResponse['data'])
                ]);

                DB::commit();

                return [
                    'status' => 'success',
                    'message' => 'Subscription activated successfully',
                    'subscription' => $subscription->load('boostPlan')
                ];
            } else {
                // Payment failed or was cancelled
                $subscription->update([
                    'status' => 'cancelled',
                    'flutterwave_response' => json_encode($verificationResponse['data'] ?? null)
                ]);

                DB::commit();

                return [
                    'status' => 'error',
                    'message' => 'Payment verification failed: ' . ($verificationResponse['message'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Boost subscription verification error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
