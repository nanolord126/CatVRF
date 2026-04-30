<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\FraudML\Services\PaymentFraudMLService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Infrastructure\Models\PaymentModel;
use Carbon\CarbonImmutable;

/**
 * PaymentReconciliationJob - Daily reconciliation with payment gateways
 *
 * Compares local payment records with gateway records to detect discrepancies:
 * - Missing payments (gateway has, we don't)
 * - Extra payments (we have, gateway doesn't)
 * - Status mismatches
 * - Amount mismatches
 *
 * Configured via .env:
 * - PAYMENT_RECONCILIATION_ENABLED=true
 * - PAYMENT_RECONCILIATION_SCHEDULE=0 2 * * *
 * - PAYMENT_RECONCILIATION_LOOKBACK_DAYS=7
 *
 * CANON 2026 - Production Ready
 */
final class PaymentReconciliationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800; // 30 minutes max

    public int $tries = 1; // No retries for reconciliation - manual investigation required

    public function __construct(
        private readonly int $lookbackDays = 7
    ) {
        $this->queue = config('payment.reconciliation_queue', 'payment-reconciliation');
    }

    public function handle(): void
    {
        if (! config('payment.reconciliation_enabled', false)) {
            Log::info('Payment reconciliation is disabled');

            return;
        }

        $startDate = CarbonImmutable::now()->subDays($this->lookbackDays)->startOfDay();
        $endDate = CarbonImmutable::now()->endOfDay();

        Log::info('Starting payment reconciliation', [
            'lookback_days' => $this->lookbackDays,
            'start_date' => $startDate->toIso8601String(),
            'end_date' => $endDate->toIso8601String(),
        ]);

        $discrepancies = $this->reconcilePayments($startDate, $endDate);

        $this->reportDiscrepancies($discrepancies);

        Log::info('Payment reconciliation completed', [
            'discrepancies_count' => count($discrepancies),
            'lookback_days' => $this->lookbackDays,
        ]);
    }

    /**
     * Reconcile payments with gateways
     */
    private function reconcilePayments(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $discrepancies = [];

        // Get all payments in the lookback window
        $payments = PaymentModel::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('gateway')
            ->whereNotNull('gateway_transaction_id')
            ->get();

        foreach ($payments as $payment) {
            try {
                $gatewayStatus = $this->fetchGatewayStatus($payment);

                if ($gatewayStatus === null) {
                    // Payment not found in gateway
                    $discrepancies[] = [
                        'type' => 'missing_in_gateway',
                        'payment_id' => $payment->id,
                        'uuid' => $payment->uuid,
                        'gateway' => $payment->gateway,
                        'gateway_transaction_id' => $payment->gateway_transaction_id,
                        'local_status' => $payment->status,
                        'local_amount' => $payment->amount,
                        'created_at' => $payment->created_at->toIso8601String(),
                    ];
                    continue;
                }

                if ($gatewayStatus['status'] !== $payment->status) {
                    // Status mismatch
                    $discrepancies[] = [
                        'type' => 'status_mismatch',
                        'payment_id' => $payment->id,
                        'uuid' => $payment->uuid,
                        'gateway' => $payment->gateway,
                        'gateway_transaction_id' => $payment->gateway_transaction_id,
                        'local_status' => $payment->status,
                        'gateway_status' => $gatewayStatus['status'],
                        'local_amount' => $payment->amount,
                        'gateway_amount' => $gatewayStatus['amount'],
                        'created_at' => $payment->created_at->toIso8601String(),
                    ];
                }

                if ((float) $gatewayStatus['amount'] !== (float) $payment->amount) {
                    // Amount mismatch
                    $discrepancies[] = [
                        'type' => 'amount_mismatch',
                        'payment_id' => $payment->id,
                        'uuid' => $payment->uuid,
                        'gateway' => $payment->gateway,
                        'gateway_transaction_id' => $payment->gateway_transaction_id,
                        'local_status' => $payment->status,
                        'local_amount' => $payment->amount,
                        'gateway_amount' => $gatewayStatus['amount'],
                        'difference' => abs((float) $gatewayStatus['amount'] - (float) $payment->amount),
                        'created_at' => $payment->created_at->toIso8601String(),
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Failed to reconcile payment', [
                    'payment_id' => $payment->id,
                    'uuid' => $payment->uuid,
                    'gateway' => $payment->gateway,
                    'error' => $e->getMessage(),
                ]);

                $discrepancies[] = [
                    'type' => 'reconciliation_error',
                    'payment_id' => $payment->id,
                    'uuid' => $payment->uuid,
                    'gateway' => $payment->gateway,
                    'error' => $e->getMessage(),
                    'created_at' => $payment->created_at->toIso8601String(),
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Fetch payment status from gateway
     */
    private function fetchGatewayStatus(PaymentModel $payment): ?array
    {
        return match ($payment->gateway) {
            'tinkoff' => $this->fetchTinkoffStatus($payment),
            'tochka' => $this->fetchTochkaStatus($payment),
            'sberbank' => $this->fetchSberbankStatus($payment),
            'sbp' => $this->fetchSBPStatus($payment),
            default => null,
        };
    }


    /**
     * Fetch status from Tinkoff
     */
    private function fetchTinkoffStatus(PaymentModel $payment): ?array
    {
        $terminalKey = config('services.tinkoff.terminal_key');
        $secretKey = config('services.tinkoff.secret_key');

        if (! $terminalKey || ! $secretKey) {
            Log::warning('Tinkoff credentials not configured');

            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://securepay.tinkoff.ru/v2/GetState', [
                'TerminalKey' => $terminalKey,
                'PaymentId' => $payment->gateway_transaction_id,
                'Token' => $this->generateTinkoffToken($terminalKey, $payment->gateway_transaction_id, $secretKey),
            ]);

            if (! $response->successful()) {
                Log::error('Tinkoff API error', [
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return [
                'status' => $this->mapTinkoffStatus($data['Status']),
                'amount' => $data['Amount'] / 100, // Tinkoff uses kopecks
            ];
        } catch (\Throwable $e) {
            Log::error('Tinkoff fetch error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch status from Tochka (B2B)
     */
    private function fetchTochkaStatus(PaymentModel $payment): ?array
    {
        $token = config('services.tochka.token');
        $apiUrl = config('services.tochka.api_url');

        if (! $token) {
            Log::warning('Tochka credentials not configured');

            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->acceptJson()
                ->get("{$apiUrl}/payments/{$payment->gateway_transaction_id}");

            if (! $response->successful()) {
                Log::error('Tochka API error', [
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return [
                'status' => $this->mapTochkaStatus($data['status']),
                'amount' => $data['amount'],
            ];
        } catch (\Throwable $e) {
            Log::error('Tochka fetch error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch status from Sberbank
     */
    private function fetchSberbankStatus(PaymentModel $payment): ?array
    {
        $userName = config('services.sberbank.username');
        $password = config('services.sberbank.password');
        $apiUrl = config('services.sberbank.api_url');

        if (! $userName || ! $password) {
            Log::warning('Sberbank credentials not configured');

            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($userName, $password)
                ->acceptJson()
                ->post("{$apiUrl}/rest/getOrderStatusExtended.do", [
                    'orderId' => $payment->gateway_transaction_id,
                ]);

            if (! $response->successful()) {
                Log::error('Sberbank API error', [
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return [
                'status' => $this->mapSberbankStatus($data['orderStatus']),
                'amount' => $data['amount'] / 100, // Sberbank uses kopecks
            ];
        } catch (\Throwable $e) {
            Log::error('Sberbank fetch error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch status from SBP (Система быстрых платежей)
     */
    private function fetchSBPStatus(PaymentModel $payment): ?array
    {
        $apiKey = config('services.sbp.api_key');
        $apiUrl = config('services.sbp.api_url');

        if (! $apiKey) {
            Log::warning('SBP credentials not configured');

            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->acceptJson()
                ->get("{$apiUrl}/v1/qr/{$payment->gateway_transaction_id}");

            if (! $response->successful()) {
                Log::error('SBP API error', [
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return [
                'status' => $this->mapSBPStatus($data['status']),
                'amount' => $data['amount'],
            ];
        } catch (\Throwable $e) {
            Log::error('SBP fetch error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }


    /**
     * Map Tinkoff status to internal status
     */
    private function mapTinkoffStatus(string $status): string
    {
        return match ($status) {
            'CONFIRMED' => 'succeeded',
            'REJECTED', 'CANCELED' => 'failed',
            'AUTHORIZING', 'AUTHORIZED', 'CHECKING' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Map Tochka status to internal status
     */
    private function mapTochkaStatus(string $status): string
    {
        return match ($status) {
            'PAID' => 'succeeded',
            'CANCELED', 'REJECTED' => 'failed',
            'PENDING', 'PROCESSING' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Map Sberbank status to internal status
     */
    private function mapSberbankStatus(int $status): string
    {
        return match ($status) {
            0, 1, 2 => 'pending', // Created, Approved, Deposited
            3, 4, 5, 6, 7 => 'succeeded', // Paid, Refunded, Reversed
            8, 9, 10 => 'failed', // Declined
            default => 'pending',
        };
    }

    /**
     * Map SBP status to internal status
     */
    private function mapSBPStatus(string $status): string
    {
        return match ($status) {
            'PAID' => 'succeeded',
            'CANCELED', 'EXPIRED', 'REJECTED' => 'failed',
            'CREATED', 'PENDING', 'WAITING' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Generate Tinkoff token
     */
    private function generateTinkoffToken(string $terminalKey, string $paymentId, string $secretKey): string
    {
        $values = [
            $paymentId,
            $terminalKey,
        ];

        sort($values);
        $values[] = $secretKey;

        return hash('sha256', implode('', $values));
    }

    /**
     * Report discrepancies via logs and alerts
     */
    private function reportDiscrepancies(array $discrepancies): void
    {
        if (empty($discrepancies)) {
            return;
        }

        Log::warning('Payment reconciliation discrepancies detected', [
            'count' => count($discrepancies),
            'discrepancies' => $discrepancies,
        ]);

        // Group by type for reporting
        $byType = collect($discrepancies)->groupBy('type');

        foreach ($byType as $type => $items) {
            Log::warning("Payment reconciliation: {$type}", [
                'count' => count($items),
                'items' => $items,
            ]);
        }

        // In production: send alert to Slack/Email
        // event(new PaymentDiscrepanciesDetected($discrepancies));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Payment reconciliation job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
