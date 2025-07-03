<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\UserBoostSubscription;

class FlutterwaveService
{
    private $secretKey;
    private $publicKey;
    private $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret_key');
        $this->publicKey = config('services.flutterwave.public_key');
        $this->baseUrl = config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3');
    }

    /**
     * Initialize a payment for boost subscription
     */
    public function initializePayment(array $paymentData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payments', [
                'tx_ref' => $paymentData['tx_ref'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'NGN',
                'redirect_url' => $paymentData['redirect_url'],
                'customer' => [
                    'email' => $paymentData['customer']['email'],
                    'phonenumber' => $paymentData['customer']['phone'] ?? null,
                    'name' => $paymentData['customer']['name'],
                ],
                'customizations' => [
                    'title' => $paymentData['title'] ?? 'Boost Subscription Payment',
                    'description' => $paymentData['description'] ?? 'Payment for boost subscription plan',
                    'logo' => $paymentData['logo'] ?? null,
                ],
                'meta' => $paymentData['meta'] ?? [],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $data = $responseData['data'];
                
                Log::info('Flutterwave payment initialization successful', [
                    'full_response' => $responseData,
                    'data_object' => $data,
                    'tx_ref' => $data['tx_ref'] ?? 'not found',
                    'id' => $data['id'] ?? 'not found',
                    'link' => $data['link'] ?? 'not found',
                    'status' => $data['status'] ?? 'not found'
                ]);
                
                return [
                    'status' => 'success',
                    'data' => $data,
                    'message' => 'Payment initialized successfully'
                ];
            }

            Log::error('Flutterwave payment initialization failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to initialize payment',
                'data' => $response->json()
            ];

        } catch (Exception $e) {
            Log::error('Flutterwave payment initialization exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify a payment transaction
     */
    public function verifyPayment(string $transactionId): array
    {
        try {
            // Check if this is a tx_ref (contains non-numeric characters)
            if (!is_numeric($transactionId)) {
                // This is a tx_ref, use the verify by tx_ref endpoint
                // Try different Flutterwave endpoints for tx_ref verification
                $endpoints = [
                    $this->baseUrl . '/transactions/verify_by_reference?tx_ref=' . urlencode($transactionId),
                    $this->baseUrl . '/transactions?tx_ref=' . urlencode($transactionId),
                    $this->baseUrl . '/transactions/verify?tx_ref=' . urlencode($transactionId)
                ];
                
                $response = null;
                $lastError = null;
                
                foreach ($endpoints as $endpoint) {
                    Log::info('Trying Flutterwave endpoint', [
                        'tx_ref' => $transactionId,
                        'endpoint' => $endpoint
                    ]);
                    
                    try {
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $this->secretKey,
                            'Content-Type' => 'application/json',
                        ])->get($endpoint);
                        
                        if ($response->successful()) {
                            Log::info('Successful response from endpoint', [
                                'endpoint' => $endpoint,
                                'status' => $response->status()
                            ]);
                            break;
                        } else {
                            $lastError = $response->json();
                            Log::warning('Failed response from endpoint', [
                                'endpoint' => $endpoint,
                                'status' => $response->status(),
                                'error' => $lastError
                            ]);
                        }
                    } catch (Exception $e) {
                        $lastError = ['error' => $e->getMessage()];
                        Log::warning('Exception from endpoint', [
                            'endpoint' => $endpoint,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // If all endpoints failed, use the last error
                if (!$response || !$response->successful()) {
                    $response = Http::response($lastError, 400);
                }
            } else {
                // This is a transaction ID, use the regular verify endpoint
                $verifyUrl = $this->baseUrl . '/transactions/' . $transactionId . '/verify';
                Log::info('Verifying payment by transaction ID', [
                    'transaction_id' => $transactionId,
                    'url' => $verifyUrl
                ]);
                
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type' => 'application/json',
                ])->get($verifyUrl);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                $data = $responseData['data'] ?? null;

                Log::info('Flutterwave payment verification successful', [
                    'transaction_id' => $transactionId,
                    'response_status' => $responseData['status'] ?? 'unknown',
                    'data_status' => $data['status'] ?? 'unknown'
                ]);

                return [
                    'status' => 'success',
                    'data' => $data,
                    'is_successful' => $data['status'] === 'successful',
                    'message' => 'Payment verification completed'
                ];
            }

            $responseData = $response->json();
            Log::error('Flutterwave payment verification failed', [
                'transaction_id' => $transactionId,
                'response' => $responseData,
                'status' => $response->status(),
                'headers' => $response->headers()
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to verify payment',
                'data' => $response->json()
            ];

        } catch (Exception $e) {
            Log::error('Flutterwave payment verification exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction(string $transactionId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/transactions/' . $transactionId);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'message' => 'Transaction details retrieved successfully'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to get transaction details',
                'data' => $response->json()
            ];

        } catch (Exception $e) {
            Log::error('Flutterwave get transaction exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to get transaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate a unique transaction reference
     */
    public function generateTransactionReference(string $prefix = 'BOOST'): string
    {
        return $prefix . '_' . time() . '_' . uniqid();
    }

    /**
     * Calculate the total amount including Flutterwave fees
     */
    public function calculateTotalAmount(float $amount, string $currency = 'NGN'): array
    {
        // Flutterwave charges 1.4% + NGN 100 for local cards
        // This is a simplified calculation - you may want to adjust based on actual fee structure
        $feePercentage = 1.4;
        $fixedFee = ($currency === 'NGN') ? 100 : 0;

        $fee = ($amount * $feePercentage / 100) + $fixedFee;
        $totalAmount = $amount + $fee;

        return [
            'original_amount' => $amount,
            'fee' => round($fee, 2),
            'total_amount' => round($totalAmount, 2)
        ];
    }

    public function verifyAndActivateSubscription(string $transactionId): array
    {
        // Find subscription by transaction_id
        $subscription = UserBoostSubscription::where('flutterwave_transaction_id', $transactionId)
            ->where('status', 'pending')
            ->first();

        // Verify with Flutterwave
        $verificationResponse = $this->verifyPayment($transactionId);

        if ($verificationResponse['status'] === 'success' && $verificationResponse['is_successful']) {
            // Activate subscription
            $subscription->update(['status' => 'active']);
            return [
                'status' => 'success',
                'message' => 'Subscription activated successfully'
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Payment verification failed'
        ];
    }
}