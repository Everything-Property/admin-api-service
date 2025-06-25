<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

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
                return [
                    'status' => 'success',
                    'data' => $response->json()['data'],
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/transactions/' . $transactionId . '/verify');

            if ($response->successful()) {
                $data = $response->json()['data'];
                
                return [
                    'status' => 'success',
                    'data' => $data,
                    'is_successful' => $data['status'] === 'successful',
                    'message' => 'Payment verification completed'
                ];
            }

            Log::error('Flutterwave payment verification failed', [
                'transaction_id' => $transactionId,
                'response' => $response->json(),
                'status' => $response->status()
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
}