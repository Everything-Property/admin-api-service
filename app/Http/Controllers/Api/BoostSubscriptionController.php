<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoostPlan;
use App\Models\UserBoostSubscription;
use App\Services\BoostSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BoostSubscriptionController extends Controller
{
    private BoostSubscriptionService $boostSubscriptionService;

    public function __construct(BoostSubscriptionService $boostSubscriptionService)
    {
        $this->boostSubscriptionService = $boostSubscriptionService;
    }

    /**
     * Get all available boost plans grouped by role
     */
    public function getAllPlans(): JsonResponse
    {
        try {
            $plans = $this->boostSubscriptionService->getAllPlansGroupedByRole();

            return response()->json([
                'status' => 'success',
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch all boost plans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available boost plans for the authenticated user or by role
     */
    public function getPlans(Request $request): JsonResponse
    {


        try {
            $user = Auth::user();
            $userRole = $request->input('user_role'); // Optional role parameter

            // dd( $userRole);
            $plans = $this->boostSubscriptionService->getAvailablePlans($user, $userRole);

            return response()->json([
                'status' => 'success',
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch boost plans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's current subscription
     */
    public function getCurrentSubscription(): JsonResponse
    {
        try {
            $user = Auth::user();
            $subscription = $this->boostSubscriptionService->getCurrentSubscription($user);

            if (!$subscription) {
                return response()->json([
                    'status' => 'success',
                    'data' => null,
                    'message' => 'No active subscription found'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $subscription->id,
                    'boost_plan' => [
                        'id' => $subscription->boostPlan->id,
                        'name' => $subscription->boostPlan->name,
                        'description' => $subscription->boostPlan->description,
                        'listing_limit' => $subscription->boostPlan->listing_limit,
                        'free_viewing_requests_per_month' => $subscription->boostPlan->free_viewing_requests_per_month,
                    ],
                    'billing_cycle' => $subscription->billing_cycle,
                    'amount_paid' => $subscription->amount_paid,
                    'discount_applied' => $subscription->discount_applied,
                    'start_date' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date->format('Y-m-d'),
                    'status' => $subscription->status,
                    'auto_renew' => $subscription->auto_renew,
                    'days_remaining' => $subscription->getRemainingDays(),
                    'is_expired' => $subscription->isExpired(),
                    'next_billing_date' => $subscription->getNextBillingDate()?->format('Y-m-d'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch current subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize subscription payment
     */
    public function initializePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'boost_plan_id' => 'required|integer|exists:boost_plans,id',
            'billing_cycle' => 'required|string|in:monthly,quarterly,yearly',
            'redirect_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $result = $this->boostSubscriptionService->initializeSubscriptionPayment(
                $user,
                $request->boost_plan_id,
                $request->billing_cycle,
                $request->redirect_url
            );

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Payment initialization failed'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initialize payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment and activate subscription
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->boostSubscriptionService->verifyAndActivateSubscription(
                $request->transaction_id
            );

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['subscription'] ?? null
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscription history for the authenticated user
     */
    public function getSubscriptionHistory(): JsonResponse
    {
        try {
            $user = Auth::user();
            $subscriptions = UserBoostSubscription::forUser($user->id)
                ->with('boostPlan')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data' => $subscriptions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch subscription history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel auto-renewal for current subscription
     */
    public function cancelAutoRenewal(): JsonResponse
    {
        try {
            $user = Auth::user();
            $subscription = $this->boostSubscriptionService->getCurrentSubscription($user);

            if (!$subscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active subscription found'
                ], 404);
            }

            $subscription->update(['auto_renew' => false]);

            return response()->json([
                'status' => 'success',
                'message' => 'Auto-renewal cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel auto-renewal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enable auto-renewal for current subscription
     */
    public function enableAutoRenewal(): JsonResponse
    {
        try {
            $user = Auth::user();
            $subscription = $this->boostSubscriptionService->getCurrentSubscription($user);

            if (!$subscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active subscription found'
                ], 404);
            }

            $subscription->update(['auto_renew' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'Auto-renewal enabled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to enable auto-renewal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check viewing request quota
     */
    public function checkViewingQuota(): JsonResponse
    {
        try {
            $user = Auth::user();
            $quotaInfo = $this->boostSubscriptionService->canMakeFreeViewingRequest($user);

            return response()->json([
                'status' => 'success',
                'data' => $quotaInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check viewing quota',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a viewing request
     */
    public function createViewingRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|integer|exists:properties,id',
            'force_paid' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $result = $this->boostSubscriptionService->createViewingRequest(
                $user,
                $request->property_id,
                $request->boolean('force_paid', false)
            );

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Viewing request created successfully',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create viewing request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
