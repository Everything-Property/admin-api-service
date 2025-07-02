<?php

namespace App\Http\Controllers;

use App\Models\PropertyRequest;
use App\Models\User;
use App\Models\PropertyRequestViewing;
use App\Services\BoostSubscriptionService;
use App\Services\FlutterwaveService;
use App\Services\HashidsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PropertyRequestController extends Controller
{
    public function __construct(
        private readonly BoostSubscriptionService $boostSubscriptionService,
        private readonly FlutterwaveService $flutterwaveService,
        private readonly HashidsService $hashidsService
    ) {}

    /**
     * Get all property requests
     */
    public function index(): JsonResponse
    {
        try {
            $propertyRequests = PropertyRequest::with([
                'propertyType',
                'category',
                'state'
            ])->orderBy('created_at', 'desc')->get();

            $data = $propertyRequests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'property_type' => $request->propertyType?->title,
                    'property_category' => $request->category?->title,
                    'state' => $request->state?->name,
                    'state_locality_id' => $request->state_locality_id,
                    'full_name' => $request->full_name,
                    'user_type' => $request->user_type,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'additional_information' => $request->additional_information,
                    'bedroom' => $request->bedroom,
                    'bathroom' => $request->bathroom,
                    'active' => $request->active,
                    'created_at' => $request->created_at,
                    'last_notified_at' => $request->last_notified_at,
                    'user_id' => $request->user_id,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch property requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific property request by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $propertyRequest = PropertyRequest::with([
                'propertyType',
                'category',
                'state'
            ])->find($id);

            if (!$propertyRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Property request not found'
                ], 404);
            }

            $data = [
                'id' => $propertyRequest->id,
                'property_type' => $propertyRequest->propertyType?->title,
                'property_category' => $propertyRequest->category?->title,
                'state' => $propertyRequest->state?->name,
                'state_locality_id' => $propertyRequest->state_locality_id,
                'full_name' => $propertyRequest->full_name,
                'user_type' => $propertyRequest->user_type,
                'email' => $propertyRequest->email,
                'phone' => $propertyRequest->phone,
                'additional_information' => $propertyRequest->additional_information,
                'bedroom' => $propertyRequest->bedroom,
                'bathroom' => $propertyRequest->bathroom,
                'active' => $propertyRequest->active,
                'created_at' => $propertyRequest->created_at,
                'last_notified_at' => $propertyRequest->last_notified_at,
                'user_id' => $propertyRequest->user_id,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch property request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get property request details with subscription/eligibility check
     */
    public function getPropertyRequestDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'property_request_id' => 'required|integer|exists:property_request,id'
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
            $propertyRequestId = $request->property_request_id;

            // Get property request details
            $propertyRequest = PropertyRequest::with([
                'propertyType',
                'category',
                'state'
            ])->find($propertyRequestId);

            if (!$propertyRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Property request not found'
                ], 404);
            }

                        // Check monthly quota for property request viewings
            $currentMonth = now()->month;
            $currentYear = now()->year;
            $freeViewingsUsed = PropertyRequestViewing::getFreeViewingsCountForMonth(
                $user->id,
                $currentYear,
                $currentMonth
            );

            // Get user's subscription to determine free viewing limit
            $subscription = $this->boostSubscriptionService->getCurrentSubscription($user);

            // Get free viewing limit based on user role and subscription
            $freeViewingsLimit = $this->getFreeViewingLimitByUserRole($user, $subscription);

            if ($freeViewingsUsed >= $freeViewingsLimit) {
                return response()->json([
                    'status' => 'quota_exceeded',
                    'message' => "You have exceeded your free property request viewing quota for this month ({$freeViewingsUsed}/{$freeViewingsLimit}). Please wait until next month or subscribe to a boost plan for more viewings.",
                    'property_request_id' => $propertyRequestId,
                    'quota_info' => [
                        'free_viewings_used' => $freeViewingsUsed,
                        'free_viewings_limit' => $freeViewingsLimit,
                        'current_month' => $currentMonth,
                        'current_year' => $currentYear,
                        'has_subscription' => $subscription ? true : false
                    ]
                ], 200);
            }

            // User is eligible to view - record the viewing
            PropertyRequestViewing::create([
                'user_id' => $user->id,
                'property_request_id' => $propertyRequestId,
                'viewing_type' => 'free',
                'amount_charged' => 0,
                'year' => $currentYear,
                'month' => $currentMonth,
                'status' => 'completed',
                'viewed_at' => now(),
                'notes' => "Free viewing of property request ID: {$propertyRequestId}"
            ]);

            // Return property request details
            $data = [
                'id' => $propertyRequest->id,
                'property_type' => $propertyRequest->propertyType?->title,
                'property_category' => $propertyRequest->category?->title,
                'state' => $propertyRequest->state?->name,
                'state_locality_id' => $propertyRequest->state_locality_id,
                'full_name' => $propertyRequest->full_name,
                'user_type' => $propertyRequest->user_type,
                'email' => $propertyRequest->email,
                'phone' => $propertyRequest->phone,
                'additional_information' => $propertyRequest->additional_information,
                'bedroom' => $propertyRequest->bedroom,
                'bathroom' => $propertyRequest->bathroom,
                'active' => $propertyRequest->active,
                'created_at' => $propertyRequest->created_at,
                'last_notified_at' => $propertyRequest->last_notified_at,
                'user_id' => $propertyRequest->user_id,
                'viewing_type' => 'free',
                'quota_info' => [
                    'free_viewings_used' => $freeViewingsUsed + 1, // +1 because we just recorded this viewing
                    'free_viewings_limit' => $freeViewingsLimit,
                    'has_subscription' => $subscription ? true : false,
                    'subscription_plan' => $subscription ? $subscription->boostPlan->name : null
                ]
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Property request details retrieved successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get property request details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get free viewing limit based on user role and subscription
     */
    private function getFreeViewingLimitByUserRole(User $user, $subscription = null): int
    {
        // If user has an active subscription, use their plan's limit
        if ($subscription) {
            return $subscription->boostPlan->free_viewing_requests_per_month;
        }

        // Get user's roles from the roles field
        $userRoles = $user->roles ?? [];

        // Determine user type based on roles
        $userType = $this->determineUserType($userRoles);

        // Get the appropriate free plan for the user type
        $freePlan = $this->getFreePlanByUserType($userType);

        return $freePlan ? $freePlan->free_viewing_requests_per_month : 0;
    }

    /**
     * Determine user type based on roles
     */
    private function determineUserType(array $roles): string
    {
        if (in_array('ROLE_BROKER', $roles) || in_array('ROLE_BROKERAGE', $roles)) {
            return 'broker';
        } elseif (in_array('ROLE_COMPANY', $roles)) {
            return 'company';
        } elseif (in_array('ROLE_DEVELOPER', $roles)) {
            return 'developer';
        }

        return 'user'; // Default for ROLE_USER only
    }

    /**
     * Get free plan for user type
     */
    private function getFreePlanByUserType(string $userType): ?object
    {
        $planName = match($userType) {
            'broker' => 'Free',
            'company' => 'Free',
            'developer' => 'Free',
            default => 'Free'
        };

        return DB::table('boost_plans')
            ->where('name', $planName)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Initialize payment for property request viewing
     */
    public function initializePropertyPayNow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'property_request_id' => 'required|integer|exists:property_request,id'
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
            $propertyRequestId = $request->property_request_id;

            // Get property request details
            $propertyRequest = PropertyRequest::with([
                'propertyType',
                'category',
                'state'
            ])->find($propertyRequestId);

            if (!$propertyRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Property request not found'
                ], 404);
            }

            // Check if user has an active subscription
            $subscription = $this->boostSubscriptionService->getCurrentSubscription($user);

            // Get free viewing limit based on user role
            $freeViewingsLimit = $this->getFreeViewingLimitByUserRole($user, $subscription);

            // Check current month's viewing count
            $currentMonth = now()->month;
            $currentYear = now()->year;
            $freeViewingsUsed = PropertyRequestViewing::getFreeViewingsCountForMonth(
                $user->id,
                $currentYear,
                $currentMonth
            );

            // If user has subscription and hasn't exceeded quota, they shouldn't need to pay
            if ($subscription && $freeViewingsUsed < $freeViewingsLimit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You still have free viewings available. No payment required.',
                    'quota_info' => [
                        'free_viewings_used' => $freeViewingsUsed,
                        'free_viewings_limit' => $freeViewingsLimit,
                        'free_viewings_remaining' => $freeViewingsLimit - $freeViewingsUsed
                    ]
                ], 400);
            }

            // Initialize Flutterwave payment
            $paymentData = [
                'tx_ref' => 'PR_' . $propertyRequestId . '_' . $user->id . '_' . time(),
                'amount' => 5000, // Fixed amount of 5000
                'currency' => 'NGN',
                'redirect_url' => 'https://localhost:3000/dashboard/property-requests',
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone_number' => $user->phone
                ],
                'meta' => [
                    'user_id' => $user->id,
                    'property_request_id' => $propertyRequestId,
                    'payment_type' => 'property_request_viewing',
                    'amount' => 5000
                ],
                'customizations' => [
                    'title' => 'Property Request Viewing Payment',
                    'description' => "Payment for viewing property request #{$propertyRequestId}",
                    'logo' => config('app.url') . '/logo.png'
                ]
            ];

            $paymentResponse = $this->flutterwaveService->initializePayment($paymentData);

                        if ($paymentResponse['status'] === 'success') {
                // Get the Flutterwave response data
                $flutterwaveData = $paymentResponse['data'];

                // Record the pending viewing
                PropertyRequestViewing::create([
                    'user_id' => $user->id,
                    'property_request_id' => $propertyRequestId,
                    'viewing_type' => 'paid',
                    'amount_charged' => 5000,
                    'year' => $currentYear,
                    'month' => $currentMonth,
                    'status' => 'pending',
                    'viewed_at' => null,
                    'flutterwave_transaction_id' => $flutterwaveData['id'] ?? $flutterwaveData['tx_ref'] ?? $paymentData['tx_ref'],
                    'notes' => "Paid viewing of property request ID: {$propertyRequestId} - Payment pending"
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment initialized successfully',
                    'data' => [
                        'payment_url' => $flutterwaveData['link'] ?? $flutterwaveData['authorization_url'] ?? null,
                        'transaction_reference' => $flutterwaveData['id'] ?? $flutterwaveData['tx_ref'] ?? $paymentData['tx_ref'],
                        'amount' => 5000,
                        'currency' => 'NGN',
                        'property_request_id' => $propertyRequestId,
                        'payment_type' => 'property_request_viewing'
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to initialize payment',
                    'error' => $paymentResponse['message'] ?? 'Payment initialization failed'
                ], 500);
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
     * Verify payment and grant access to property request details
     */
    public function verifyPropertyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_reference' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transactionRef = $request->transaction_reference;

            // Verify payment with Flutterwave
            $verificationResponse = $this->flutterwaveService->verifyPayment($transactionRef);

            if ($verificationResponse['status'] === 'success' && $verificationResponse['data']['status'] === 'successful') {
                // Find the pending viewing record
                $viewing = PropertyRequestViewing::where('flutterwave_transaction_id', $transactionRef)
                    ->where('status', 'pending')
                    ->first();

                if (!$viewing) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment record not found'
                    ], 404);
                }

                // Update viewing status to completed
                $viewing->update([
                    'status' => 'completed',
                    'viewed_at' => now(),
                    'notes' => "Paid viewing of property request ID: {$viewing->property_request_id} - Payment verified"
                ]);

                // Get property request details
                $propertyRequest = PropertyRequest::with([
                    'propertyType',
                    'category',
                    'state'
                ])->find($viewing->property_request_id);

                if (!$propertyRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Property request not found'
                    ], 404);
                }

                // Return property request details
                $data = [
                    'id' => $propertyRequest->id,
                    'property_type' => $propertyRequest->propertyType?->title,
                    'property_category' => $propertyRequest->category?->title,
                    'state' => $propertyRequest->state?->name,
                    'state_locality_id' => $propertyRequest->state_locality_id,
                    'full_name' => $propertyRequest->full_name,
                    'user_type' => $propertyRequest->user_type,
                    'email' => $propertyRequest->email,
                    'phone' => $propertyRequest->phone,
                    'additional_information' => $propertyRequest->additional_information,
                    'bedroom' => $propertyRequest->bedroom,
                    'bathroom' => $propertyRequest->bathroom,
                    'active' => $propertyRequest->active,
                    'created_at' => $propertyRequest->created_at,
                    'last_notified_at' => $propertyRequest->last_notified_at,
                    'user_id' => $propertyRequest->user_id,
                    'viewing_type' => 'paid',
                    'payment_info' => [
                        'transaction_reference' => $transactionRef,
                        'amount_paid' => $viewing->amount_charged,
                        'payment_status' => 'completed'
                    ]
                ];

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment verified successfully. Property request details retrieved.',
                    'data' => $data
                ]);

            } else {
                // Update viewing status to failed
                PropertyRequestViewing::where('flutterwave_transaction_id', $transactionRef)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'notes' => "Payment verification failed for transaction: {$transactionRef}"
                    ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment verification failed',
                    'error' => $verificationResponse['message'] ?? 'Payment was not successful'
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
     * Get user's property request viewing quota information
     */
    public function getViewingQuota(Request $request): JsonResponse
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

                        // Get current month's viewing count
            $currentMonth = now()->month;
            $currentYear = now()->year;
            $freeViewingsUsed = PropertyRequestViewing::getFreeViewingsCountForMonth(
                $user->id,
                $currentYear,
                $currentMonth
            );

            // Check if user has an active boost subscription
            $subscription = $this->boostSubscriptionService->getCurrentSubscription($user);

            // Default free viewing limit (for users without subscription)
            $freeViewingsLimit = 3; // Default free viewings per month

            if ($subscription) {
                $freeViewingsLimit = $subscription->boostPlan->free_viewing_requests_per_month;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Quota information retrieved successfully',
                'data' => [
                    'has_subscription' => $subscription ? true : false,
                    'subscription_details' => $subscription ? [
                        'plan_name' => $subscription->boostPlan->name,
                        'plan_description' => $subscription->boostPlan->description,
                        'free_viewing_requests_per_month' => $freeViewingsLimit,
                        'subscription_status' => $subscription->status,
                        'end_date' => $subscription->end_date
                    ] : null,
                    'quota_info' => [
                        'free_viewings_used' => $freeViewingsUsed,
                        'free_viewings_limit' => $freeViewingsLimit,
                        'free_viewings_remaining' => max(0, $freeViewingsLimit - $freeViewingsUsed),
                        'current_month' => $currentMonth,
                        'current_year' => $currentYear,
                        'quota_exceeded' => $freeViewingsUsed >= $freeViewingsLimit
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get viewing quota',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
