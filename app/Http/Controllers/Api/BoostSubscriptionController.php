<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoostPlan;
use App\Models\User;
use App\Models\UserBoostSubscription;
use App\Services\BoostSubscriptionService;
use App\Services\HashidsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class BoostSubscriptionController extends Controller
{
    private BoostSubscriptionService $boostSubscriptionService;
    private HashidsService $hashidsService;

    public function __construct(
        BoostSubscriptionService $boostSubscriptionService,
        HashidsService $hashidsService
    ) {
        $this->boostSubscriptionService = $boostSubscriptionService;
        $this->hashidsService = $hashidsService;
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
     * Get available boost plans for a user or by role
     */
    public function getPlans(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|string',
            'user_role' => 'nullable|string|in:broker,company,developer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = null;
            if ($request->has('user_id') && $request->user_id) {
                $decodedUserId = $this->hashidsService->decode($request->user_id);
                if ($decodedUserId) {
                    $user = User::find($decodedUserId);
                }
            }

            $userRole = $request->input('user_role');
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
    public function getCurrentSubscription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $decodedUserId = $this->hashidsService->decode($request->user_id);
            if (!$decodedUserId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID'
                ], 400);
            }

            $user = User::findOrFail($decodedUserId);
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
            'user_id' => 'required|string',
            'boost_plan_id' => 'required|integer|exists:boost_plans,id',
            'billing_cycle' => 'required|string|in:monthly,quarterly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $decodedUserId = $this->hashidsService->decode($request->user_id);

            if (!$decodedUserId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID'
                ], 400);
            }

            $user = User::findOrFail($decodedUserId);

            // Use default redirect URL from configuration
            $redirectUrl = config('services.flutterwave.redirect_url', 'https://everythingproperty.ng/payment/callback');

            $result = $this->boostSubscriptionService->initializeSubscriptionPayment(
                $user,
                $request->boost_plan_id,
                $request->billing_cycle,
                $redirectUrl
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
     * Get user from request (decoded from Symfony's hashids)
     */
    private function getUserFromRequest(Request $request): ?User
    {
        $encodedUserId = $request->header('X-User-ID');

        if (!$encodedUserId) {
            return null;
        }

        try {
            $userId = $this->hashidsService->decode($encodedUserId);
            return User::find($userId);
        } catch (Exception $e) {
            Log::warning('Failed to decode user ID from request', [
                'encoded_user_id' => $encodedUserId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verify payment and activate subscription
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string'
        ]);

        $transactionId = $request->input('transaction_id');
        $user = $this->getUserFromRequest($request);

        $result = $this->boostSubscriptionService->verifyAndActivateSubscription($transactionId, $user);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'subscription' => $result['subscription']
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Get subscription history for a user
     */
    public function getSubscriptionHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $decodedUserId = $this->hashidsService->decode($request->user_id);
            if (!$decodedUserId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID'
                ], 400);
            }

            $user = User::findOrFail($decodedUserId);
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
    public function cancelAutoRenewal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $decodedUserId = $this->hashidsService->decode($request->user_id);
            if (!$decodedUserId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID'
                ], 400);
            }

            $user = User::findOrFail($decodedUserId);
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
    public function enableAutoRenewal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $decodedUserId = $this->hashidsService->decode($request->user_id);
            if (!$decodedUserId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID'
                ], 400);
            }

            $user = User::findOrFail($decodedUserId);
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
    public function checkViewingQuota(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $decodedUserId = $this->hashidsService->decode($request->user_id);
            if (!$decodedUserId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID'
                ], 400);
            }

            $user = User::findOrFail($decodedUserId);
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
            'user_id' => 'required|string',
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
            $decodedUserId = $this->hashidsService->decode($request->user_id);
            if (!$decodedUserId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid user ID'
                ], 400);
            }

            $user = User::findOrFail($decodedUserId);
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
