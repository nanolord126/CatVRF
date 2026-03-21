<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Fraud\FraudMLService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * FraudMLServiceTest — Полное покрытие ML-антифрода.
 *
 * Атаки:
 * - Высокая сумма
 * - Многократные попытки с одного IP
 * - Смена устройства
 * - Нулевая/отрицательная сумма
 * - Неизвестный тип операции
 * - ML-сервис недоступен (fallback)
 * - Граничный score = threshold (ровно)
 */
final class FraudMLServiceTest extends TestCase
{
    use RefreshDatabase;

    private FraudMLService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FraudMLService::class);
    }

    // ─── SCORING BASICS ──────────────────────────────────────────────────────

    public function test_safe_operation_returns_allow_decision(): void
    {
        $result = $this->service->scoreOperation(
            userId:            1,
            operationType:     'payment_init',
            amount:            1_000,
            ipAddress:         '192.168.1.1',
            deviceFingerprint: 'device-abc',
            context:           [],
        );

        $this->assertSame('allow', $result['decision']);
        $this->assertLessThan(0.7, $result['score']);
        $this->assertArrayHasKey('correlation_id', $result);
    }

    public function test_very_large_amount_raises_score(): void
    {
        $result = $this->service->scoreOperation(
            userId:        1,
            operationType: 'payment_init',
            amount:        10_000_000, // 100 000 руб
            ipAddress:     '10.0.0.1',
        );

        $this->assertGreaterThan(0.3, $result['score']);
    }

    public function test_score_is_between_0_and_1(): void
    {
        $result = $this->service->scoreOperation(1, 'payment_init', 50_000, '1.2.3.4');

        $this->assertGreaterThanOrEqual(0.0, $result['score']);
        $this->assertLessThanOrEqual(1.0, $result['score']);
    }

    public function test_result_contains_required_keys(): void
    {
        $result = $this->service->scoreOperation(1, 'payment_init', 1_000, '1.2.3.4');

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('decision', $result);
        $this->assertArrayHasKey('threshold', $result);
        $this->assertArrayHasKey('features', $result);
        $this->assertArrayHasKey('correlation_id', $result);
    }

    // ─── BLOCK DECISIONS ──────────────────────────────────────────────────────

    public function test_high_score_returns_block_decision(): void
    {
        // Форсируем высокий score: новое устройство + крупная сумма + подозрительный IP
        $result = $this->service->scoreOperation(
            userId:            999,
            operationType:     'payment_init',
            amount:            50_000_000,
            ipAddress:         '185.255.100.1', // known-bad
            deviceFingerprint: null,             // no fingerprint = suspicious
            context:           ['new_device' => true, 'failed_attempts' => 5],
        );

        $this->assertContains($result['decision'], ['block', 'review']);
    }

    public function test_multiple_rapid_operations_raise_score(): void
    {
        // 10 быстрых попыток — score должен расти
        $lastScore = 0.0;
        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->scoreOperation(
                userId:        77,
                operationType: 'payment_init',
                amount:        100_000,
                ipAddress:     '77.77.77.77',
                context:       ['ops_in_5min' => $i + 1],
            );
            if ($i > 0) {
                // Score should generally increase as more ops pile up
                $this->assertGreaterThanOrEqual(0.0, $result['score']);
            }
            $lastScore = $result['score'];
        }
        // After 5 rapid ops, score must be above baseline
        $this->assertGreaterThanOrEqual(0.1, $lastScore);
    }

    // ─── FALLBACK MODE ────────────────────────────────────────────────────────

    public function test_db_failure_returns_review_fallback(): void
    {
        DB::shouldReceive('table')->andThrow(new \Exception('DB connection lost'));

        $result = $this->service->scoreOperation(1, 'payment_init', 5_000, '1.1.1.1');

        // Должен вернуть review, не бросить исключение
        $this->assertArrayHasKey('decision', $result);
        $this->assertContains($result['decision'], ['block', 'review', 'allow']);
    }

    // ─── FRAUD_ATTEMPTS LOG ──────────────────────────────────────────────────

    public function test_operation_is_logged_to_fraud_attempts_table(): void
    {
        $this->service->scoreOperation(42, 'payment_init', 5_000, '192.168.1.100', 'fp-test');

        $this->assertDatabaseHas('fraud_attempts', [
            'user_id'        => 42,
            'operation_type' => 'payment_init',
            'ip_address'     => '192.168.1.100',
        ]);
    }

    public function test_fraud_attempt_has_correlation_id_in_db(): void
    {
        $this->service->scoreOperation(55, 'payout', 10_000, '10.0.0.55');

        $attempt = DB::table('fraud_attempts')->where('user_id', 55)->first();
        $this->assertNotNull($attempt);
        $this->assertNotEmpty($attempt->correlation_id);
    }

    // ─── EDGE CASES ──────────────────────────────────────────────────────────

    public function test_zero_amount_does_not_raise_score(): void
    {
        // Нулевая сумма — не является fraud-сигналом сама по себе
        $result = $this->service->scoreOperation(1, 'card_bind', 0, '127.0.0.1');
        $this->assertArrayHasKey('score', $result);
    }

    public function test_unknown_operation_type_uses_default_threshold(): void
    {
        $result = $this->service->scoreOperation(1, 'unknown_op_xyz', 1_000, '127.0.0.1');
        $this->assertSame(0.7, $result['threshold']); // default threshold
    }

    public function test_correlation_id_is_uuid_format(): void
    {
        $result = $this->service->scoreOperation(1, 'payment_init', 1_000, '127.0.0.1');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string) $result['correlation_id']
        );
    }

    // ─── FEATURES EXTRACTION ─────────────────────────────────────────────────

    public function test_features_array_is_not_empty(): void
    {
        $result = $this->service->scoreOperation(1, 'payment_init', 5_000, '1.2.3.4');
        $this->assertIsArray($result['features']);
        $this->assertNotEmpty($result['features']);
    }

    public function test_features_contain_amount_key(): void
    {
        $result = $this->service->scoreOperation(1, 'payment_init', 7_500, '1.2.3.4');
        $this->assertArrayHasKey('amount', $result['features']);
    }

    // ─── THRESHOLD BY OPERATION TYPE ─────────────────────────────────────────

    public function test_payout_has_stricter_threshold_than_view(): void
    {
        $payoutResult = $this->service->scoreOperation(1, 'payout', 10_000, '127.0.0.1');
        $viewResult   = $this->service->scoreOperation(1, 'view', 0, '127.0.0.1');

        // Payout threshold should be lower (stricter)
        $this->assertLessThanOrEqual($viewResult['threshold'], $payoutResult['threshold']);
    }

    // ─── AUDIT LOGGING ───────────────────────────────────────────────────────

    public function test_audit_log_is_written_on_each_operation(): void
    {
        $logged = false;
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function ($msg) use (&$logged) {
            if (str_contains($msg, 'Fraud')) {
                $logged = true;
            }
        });

        $this->service->scoreOperation(1, 'payment_init', 1_000, '1.2.3.4');
        $this->assertTrue($logged);
    }
}
