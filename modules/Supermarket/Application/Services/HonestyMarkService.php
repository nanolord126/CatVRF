<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Domain\Exceptions\HonestyMarkException;
use Modules\Supermarket\Infrastructure\Models\ProductMark;
use Modules\Supermarket\Infrastructure\Models\OrderItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class HonestyMarkService
{
    use WithAuditLogging;
    use WithTelemetry;

    private string $apiUrl;
    private string $token;
    private string $inn;
    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->apiUrl = config('honestysign.api_url', env('HONESTY_SIGN_API_URL'));
        $this->token = config('honestysign.token', env('HONESTY_SIGN_TOKEN'));
        $this->inn = config('honestysign.inn', env('COMPANY_INN'));
        $this->fraudControl = $fraudControl;
    }

    public function validateMark(string $dataMatrix, string $gtin): array
    {
        return $this->withSpan(
            'honesty_mark.validate',
            function () use ($dataMatrix, $gtin) {
                // Fraud check before validating mark
                $this->fraudControl->check([
                    'operation_type' => 'honesty_mark_validate',
                    'vertical' => 'supermarket',
                    'gtin' => $gtin,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                // Circuit breaker check
                $cbKey = 'honesty_mark:cb:open';
                if (\Illuminate\Support\Facades\Cache::get($cbKey)) {
                    Log::warning('Honesty Mark circuit breaker is open', ['gtin' => \Illuminate\Support\Str::mask($gtin, '*', 4, 8)]);
                    return [
                        'valid' => false,
                        'status' => 'circuit_open',
                        'error' => 'Service temporarily unavailable',
                    ];
                }

                if (empty($this->apiUrl) || empty($this->token)) {
                    Log::warning('Honesty Mark credentials not configured');
                    return [
                        'valid' => false,
                        'status' => 'not_configured',
                        'error' => 'Service not configured',
                    ];
                }

                try {
                    $response = Http::timeout(10)
                        ->retry(3, function ($attempt) {
                            return 1000 * pow(2, $attempt - 1); // Exponential backoff
                        })
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->token,
                            'Content-Type' => 'application/json',
                        ])
                        ->post($this->apiUrl . '/api/v3/facade/mark/check', [
                            'mark' => $dataMatrix,
                            'gtin' => $gtin,
                        ]);

                    if ($response->successful()) {
                        $result = $response->json();

                        Log::info('Honesty Mark validation success', [
                            'gtin' => \Illuminate\Support\Str::mask($gtin, '*', 4, 8),
                            'valid' => $result['valid'] ?? false,
                        ]);

                        // Reset failures on success
                        \Illuminate\Support\Facades\Cache::forget('honesty_mark:failures');

                        return [
                            'valid' => $result['valid'] ?? false,
                            'status' => $result['status'] ?? 'unknown',
                            'error' => $result['error'] ?? null,
                        ];
                    }

                    // Circuit breaker: increment failure counter
                    $failures = \Illuminate\Support\Facades\Cache::increment('honesty_mark:failures', 1, 300);
                    if ($failures >= 5) {
                        \Illuminate\Support\Facades\Cache::put($cbKey, true, now()->addMinutes(5));
                        Log::error('Honesty Mark circuit breaker opened after 5 failures', ['gtin' => \Illuminate\Support\Str::mask($gtin, '*', 4, 8)]);
                    }

                    Log::error('Honesty Mark validation failed', [
                        'gtin' => \Illuminate\Support\Str::mask($gtin, '*', 4, 8),
                        'status' => $response->status(),
                    ]);

                    return [
                        'valid' => false,
                        'status' => 'error',
                        'error' => 'Service returned error: ' . $response->status(),
                    ];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    
                    // Circuit breaker: increment failure counter on exception
                    $failures = \Illuminate\Support\Facades\Cache::increment('honesty_mark:failures', 1, 300);
                    if ($failures >= 5) {
                        \Illuminate\Support\Facades\Cache::put($cbKey, true, now()->addMinutes(5));
                        Log::error('Honesty Mark circuit breaker opened after 5 failures');
                    }

                    Log::error('Honesty Mark check exception', [
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'valid' => false,
                        'status' => 'unavailable',
                        'error' => 'Service unavailable',
                    ];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'honesty_mark_validate',
            ),
        );
    }

    public function withdraw(ProductMark $mark, OrderItem $item): bool
    {
        return $this->withSpan(
            'honesty_mark.withdraw',
            function () use ($mark, $item) {
                // Fraud check before withdrawing mark
                $this->fraudControl->check([
                    'operation_type' => 'honesty_mark_withdraw',
                    'vertical' => 'supermarket',
                    'mark_id' => $mark->id,
                    'order_item_id' => $item->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                // Circuit breaker check
                $cbKey = 'honesty_mark:cb:open';
                if (\Illuminate\Support\Facades\Cache::get($cbKey)) {
                    Log::warning('Honesty Mark circuit breaker is open, skipping withdrawal');
                    return false;
                }

                if (empty($this->apiUrl) || empty($this->token)) {
                    Log::warning('Honesty Mark credentials not configured, skipping withdrawal');
                    return false;
                }

                try {
                    $response = Http::timeout(15)
                        ->retry(3, function ($attempt) {
                            return 1000 * pow(2, $attempt - 1);
                        })
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->token,
                            'Content-Type' => 'application/json',
                        ])
                        ->post($this->apiUrl . '/api/v3/facade/mark/withdraw', [
                            'mark' => $mark->data_matrix,
                            'gtin' => $item->product->gtin ?? null,
                            'inn' => $this->inn,
                        ]);

                    if ($response->successful()) {
                        $mark->update([
                            'withdrawn_at' => now(),
                            'withdraw_status' => 'success',
                        ]);

                        Log::info('Honesty Mark withdrawal success', [
                            'mark_id' => $mark->id,
                            'order_item_id' => $item->id,
                        ]);

                        // Reset failures on success
                        \Illuminate\Support\Facades\Cache::forget('honesty_mark:failures');

                        return true;
                    }

                    // Circuit breaker: increment failure counter
                    $failures = \Illuminate\Support\Facades\Cache::increment('honesty_mark:failures', 1, 300);
                    if ($failures >= 5) {
                        \Illuminate\Support\Facades\Cache::put($cbKey, true, now()->addMinutes(5));
                    }

                    Log::error('Honesty Mark withdrawal failed', [
                        'mark_id' => $mark->id,
                        'status' => $response->status(),
                    ]);

                    throw HonestyMarkException::withdrawalFailed($response->body());
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    
                    // Circuit breaker: increment failure counter
                    $failures = \Illuminate\Support\Facades\Cache::increment('honesty_mark:failures', 1, 300);
                    if ($failures >= 5) {
                        \Illuminate\Support\Facades\Cache::put($cbKey, true, now()->addMinutes(5));
                    }

                    Log::error('Honesty Mark withdrawal exception', [
                        'mark_id' => $mark->id,
                        'error' => $e->getMessage(),
                    ]);

                    throw HonestyMarkException::withdrawalFailed($e->getMessage());
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'honesty_mark_withdraw',
            ),
        );
    }

    public function batchWithdraw(array $marks): array
    {
        return $this->withSpan(
            'honesty_mark.batch_withdraw',
            function () use ($marks) {
                // Fraud check before batch withdrawal
                $this->fraudControl->check([
                    'operation_type' => 'honesty_mark_batch_withdraw',
                    'vertical' => 'supermarket',
                    'marks_count' => count($marks),
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                // Circuit breaker check
                $cbKey = 'honesty_mark:cb:open';
                if (\Illuminate\Support\Facades\Cache::get($cbKey)) {
                    Log::warning('Honesty Mark circuit breaker is open, skipping batch withdrawal');
                    return [
                        'success' => false,
                        'status' => 'circuit_open',
                        'error' => 'Service temporarily unavailable',
                    ];
                }

                if (empty($this->apiUrl) || empty($this->token)) {
                    Log::warning('Honesty Mark credentials not configured, skipping batch withdrawal');
                    return [
                        'success' => false,
                        'status' => 'not_configured',
                        'error' => 'Service not configured',
                    ];
                }

                try {
                    $response = Http::timeout(30)
                        ->retry(3, function ($attempt) {
                            return 1000 * pow(2, $attempt - 1);
                        })
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->token,
                            'Content-Type' => 'application/json',
                        ])
                        ->post($this->apiUrl . '/api/v3/facade/mark/batch-withdraw', [
                            'marks' => collect($marks)->pluck('data_matrix')->toArray(),
                            'inn' => $this->inn,
                        ]);

                    if ($response->successful()) {
                        $result = $response->json();

                        ProductMark::whereIn('id', collect($marks)->pluck('id'))->update([
                            'withdrawn_at' => now(),
                            'withdraw_status' => 'success',
                        ]);

                        Log::info('Honesty Mark batch withdrawal success', [
                            'marks_count' => count($marks),
                        ]);

                        // Reset failures on success
                        \Illuminate\Support\Facades\Cache::forget('honesty_mark:failures');

                        return [
                            'success' => true,
                            'processed' => $result['processed'] ?? count($marks),
                            'failed' => $result['failed'] ?? 0,
                        ];
                    }

                    // Circuit breaker: increment failure counter
                    $failures = \Illuminate\Support\Facades\Cache::increment('honesty_mark:failures', 1, 300);
                    if ($failures >= 5) {
                        \Illuminate\Support\Facades\Cache::put($cbKey, true, now()->addMinutes(5));
                    }

                    Log::error('Honesty Mark batch withdrawal failed', [
                        'status' => $response->status(),
                    ]);

                    throw HonestyMarkException::withdrawalFailed($response->body());
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    
                    // Circuit breaker: increment failure counter
                    $failures = \Illuminate\Support\Facades\Cache::increment('honesty_mark:failures', 1, 300);
                    if ($failures >= 5) {
                        \Illuminate\Support\Facades\Cache::put($cbKey, true, now()->addMinutes(5));
                    }

                    Log::error('Honesty Mark batch withdrawal exception', [
                        'error' => $e->getMessage(),
                    ]);

                    throw HonestyMarkException::withdrawalFailed($e->getMessage());
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'honesty_mark_batch_withdraw',
            ),
        );
    }
}
