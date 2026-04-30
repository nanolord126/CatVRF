<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Adapters;

use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tochka Bank Adapter for Supermarket vertical.
 * 
 * Provides integration with Tochka Bank API for B2B payments:
 * - Invoice creation
 * - Payment status checking
 * - Account verification
 * - Transaction history
 */
final class TochkaBankAdapter
{
    use WithTelemetry;

    private string $apiKey;
    private string $secretKey;
    private string $endpoint;
    private string $merchantId;

    public function __construct()
    {
        $this->apiKey = config('supermarket.tochka_bank.api_key', '');
        $this->secretKey = config('supermarket.tochka_bank.secret_key', '');
        $this->endpoint = config('supermarket.tochka_bank.endpoint', 'https://api.tochka.com/api/v1');
        $this->merchantId = config('supermarket.tochka_bank.merchant_id', '');
    }

    /**
     * Create an invoice for B2B payment.
     */
    public function createInvoice(array $invoiceData): array
    {
        return $this->withSpan(
            'tochka_bank.create_invoice',
            function () use ($invoiceData) {
                try {
                    $response = Http::timeout(15)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
                            'Content-Type' => 'application/json',
                            'X-Merchant-Id' => $this->merchantId,
                        ])
                        ->post("{$this->endpoint}/invoices", [
                            'amount' => $invoiceData['amount'],
                            'currency' => $invoiceData['currency'] ?? 'RUB',
                            'description' => $invoiceData['description'] ?? 'Оплата заказа',
                            'order_id' => $invoiceData['order_id'],
                            'customer' => [
                                'inn' => $invoiceData['customer']['inn'] ?? null,
                                'company_name' => $invoiceData['customer']['company_name'] ?? null,
                                'email' => $invoiceData['customer']['email'] ?? null,
                            ],
                            'due_date' => $invoiceData['due_date'] ?? now()->addDays(7)->format('Y-m-d'),
                            'metadata' => $invoiceData['metadata'] ?? [],
                        ]);

                    if (!$response->successful()) {
                        Log::error('Tochka Bank invoice creation failed', [
                            'status' => $response->status(),
                            'order_id' => $invoiceData['order_id'],
                            'body' => $response->body(),
                        ]);
                        
                        return [
                            'success' => false,
                            'error' => 'API request failed',
                            'status_code' => $response->status(),
                        ];
                    }

                    $data = $response->json();
                    
                    return [
                        'success' => true,
                        'invoice_id' => $data['id'] ?? null,
                        'invoice_url' => $data['payment_url'] ?? null,
                        'status' => $data['status'] ?? 'pending',
                        'amount' => $data['amount'] ?? 0,
                    ];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Tochka Bank invoice creation error', [
                        'order_id' => $invoiceData['order_id'],
                        'error' => $e->getMessage(),
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'tochka_bank_create_invoice',
            ),
        );
    }

    /**
     * Check payment status for an invoice.
     */
    public function checkPaymentStatus(string $invoiceId): array
    {
        return $this->withSpan(
            'tochka_bank.check_status',
            function () use ($invoiceId) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
                            'Content-Type' => 'application/json',
                            'X-Merchant-Id' => $this->merchantId,
                        ])
                        ->get("{$this->endpoint}/invoices/{$invoiceId}");

                    if (!$response->successful()) {
                        Log::error('Tochka Bank status check failed', [
                            'status' => $response->status(),
                            'invoice_id' => $invoiceId,
                        ]);
                        
                        return [
                            'success' => false,
                            'error' => 'API request failed',
                        ];
                    }

                    $data = $response->json();
                    
                    return [
                        'success' => true,
                        'status' => $data['status'] ?? 'unknown',
                        'paid_at' => $data['paid_at'] ?? null,
                        'amount' => $data['amount'] ?? 0,
                    ];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Tochka Bank status check error', [
                        'invoice_id' => $invoiceId,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'tochka_bank_check_status',
            ),
        );
    }

    /**
     * Verify company account by INN.
     */
    public function verifyAccount(string $inn): array
    {
        return $this->withSpan(
            'tochka_bank.verify_account',
            function () use ($inn) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
                            'Content-Type' => 'application/json',
                            'X-Merchant-Id' => $this->merchantId,
                        ])
                        ->get("{$this->endpoint}/accounts/verify", [
                            'inn' => $inn,
                        ]);

                    if (!$response->successful()) {
                        return [
                            'verified' => false,
                            'error' => 'Verification failed',
                        ];
                    }

                    $data = $response->json();
                    
                    return [
                        'verified' => $data['verified'] ?? false,
                        'company_name' => $data['company_name'] ?? null,
                        'account_status' => $data['status'] ?? 'unknown',
                    ];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Tochka Bank account verification error', [
                        'inn' => $inn,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return [
                        'verified' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'tochka_bank_verify_account',
            ),
        );
    }

    /**
     * Get transaction history for a period.
     */
    public function getTransactionHistory(string $startDate, string $endDate, int $page = 1): array
    {
        return $this->withSpan(
            'tochka_bank.get_transactions',
            function () use ($startDate, $endDate, $page) {
                try {
                    $response = Http::timeout(20)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
                            'Content-Type' => 'application/json',
                            'X-Merchant-Id' => $this->merchantId,
                        ])
                        ->get("{$this->endpoint}/transactions", [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'page' => $page,
                            'per_page' => 100,
                        ]);

                    if (!$response->successful()) {
                        Log::error('Tochka Bank transaction history failed', [
                            'status' => $response->status(),
                        ]);
                        
                        return [
                            'success' => false,
                            'error' => 'API request failed',
                        ];
                    }

                    $data = $response->json();
                    
                    return [
                        'success' => true,
                        'transactions' => $data['data'] ?? [],
                        'total' => $data['total'] ?? 0,
                        'page' => $page,
                        'per_page' => $data['per_page'] ?? 100,
                    ];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Tochka Bank transaction history error', [
                        'error' => $e->getMessage(),
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'tochka_bank_get_transactions',
            ),
        );
    }

    /**
     * Generate authentication token for API requests.
     */
    private function generateAuthToken(): string
    {
        $timestamp = time();
        $signature = hash_hmac(
            'sha256',
            $this->apiKey . $timestamp,
            $this->secretKey
        );

        return base64_encode("{$this->apiKey}:{$timestamp}:{$signature}");
    }
}
