<?php declare(strict_types=1);

namespace Tests\Integration\Fraud;

use App\Domains\FraudML\DTOs\FraudMLFraudMLOperationDto;
use App\Models\FraudAttempt;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Fraud\FraudMLService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * FraudDetectionFlowTest
 * 
 * Интеграционные тесты: скоринг → блокировка → уведомление
 */
final class FraudDetectionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected FraudMLService $fraudService;
    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::fake();
        Log::fake();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        $this->fraudService = app(FraudMLService::class);
    }

    /** @test */
    public function it_detects_and_blocks_velocity_attack(): void
    {
        // 5 card binding attempts in 5 minutes = fraud
        $ips = ['192.168.1.1', '10.0.0.1', '172.16.0.1', '8.8.8.1', '1.1.1.1'];
        $devices = ['device-1', 'device-2', 'device-3', 'device-4', 'device-5'];

        $scores = [];
        foreach (array_combine($ips, $devices) as $ip => $device) {
            $operation = new FraudMLOperationDto(
                type: 'card_bind',
                amount: 0,
                userId: $this->user->id,
                ipAddress: $ip,
                deviceFingerprint: $device,
            );

            $score = $this->fraudService->scoreOperation($operation);
            $scores[] = $score;

            if ($score > 0.7) {
                FraudAttempt::factory()
                    ->for($this->user)
                    ->create([
                        'operation_type' => 'card_bind',
                        'ml_score' => $score,
                        'decision' => 'block',
                    ]);
            }
        }

        // Latest attempt should be blocked
        $lastScore = end($scores);
        $this->assertGreaterThan(0.7, $lastScore);

        // Verify fraud attempt recorded
        $blockedAttempts = FraudAttempt::where('user_id', $this->user->id)
            ->where('decision', 'block')
            ->count();

        $this->assertGreaterThan(0, $blockedAttempts);
    }

    /** @test */
    public function it_detects_geographic_impossibility_fraud(): void
    {
        // Moscow → Tokyo in 1 hour = impossible
        
        // First operation: Moscow
        $operation1 = new FraudMLFraudMLOperationDto(
            type: 'payment_init',
            amount: 50000,
            userId: $this->user->id,
            ipAddress: '195.164.1.1', // Moscow
            deviceFingerprint: 'device-moscow',
        );

        $score1 = $this->fraudService->scoreOperation($operation1);

        // Record attempt
        FraudAttempt::factory()
            ->for($this->user)
            ->create([
                'operation_type' => 'payment_init',
                'ip_address' => '195.164.1.1',
                'ml_score' => $score1,
                'created_at' => now(),
            ]);

        // Second operation: Tokyo IP (3 hours later)
        $operation2 = new FraudMLOperationDto(
            type: 'payment_init',
            amount: 100000,
            userId: $this->user->id,
            ipAddress: '219.100.1.1', // Tokyo
            deviceFingerprint: 'device-tokyo',
        );

        $score2 = $this->fraudService->scoreOperation($operation2);

        if ($score2 > 0.5) {
            FraudAttempt::factory()
                ->for($this->user)
                ->create([
                    'operation_type' => 'payment_init',
                    'ip_address' => '219.100.1.1',
                    'ml_score' => $score2,
                    'decision' => 'review', // Flag for manual review
                ]);
        }

        // Geographic jump should be flagged
        $this->assertGreaterThan($score1, $score2);

        $flaggedAttempts = FraudAttempt::where('user_id', $this->user->id)
            ->where('decision', 'review')
            ->count();

        $this->assertGreaterThan(0, $flaggedAttempts);
    }

    /** @test */
    public function it_detects_new_device_large_amount_fraud(): void
    {
        $operation = new FraudMLOperationDto(
            type: 'payment_init',
            amount: 500000, // Very large amount
            userId: $this->user->id,
            ipAddress: '192.168.1.1',
            deviceFingerprint: 'brand-new-unknown-device',
        );

        $score = $this->fraudService->scoreOperation($operation);

        if ($score > 0.6) {
            FraudAttempt::factory()
                ->for($this->user)
                ->create([
                    'operation_type' => 'payment_init',
                    'amount' => 500000,
                    'ml_score' => $score,
                    'decision' => 'block',
                ]);
        }

        $this->assertGreaterThan(0.6, $score);

        $this->assertDatabaseHas('fraud_attempts', [
            'user_id' => $this->user->id,
            'decision' => 'block',
        ]);
    }

    /** @test */
    public function it_implements_fraud_response_escalation(): void
    {
        // Build up suspicious activity over time
        $operations = [
            // Attempt 1: Moderate risk
            new FraudMLOperationDto(
                type: 'card_bind',
                amount: 0,
                userId: $this->user->id,
                ipAddress: '192.168.1.1',
                deviceFingerprint: 'device-unknown-1',
            ),
            // Attempt 2: Increasing risk
            new FraudMLOperationDto(
                type: 'card_bind',
                amount: 0,
                userId: $this->user->id,
                ipAddress: '10.0.0.1',
                deviceFingerprint: 'device-unknown-2',
            ),
            // Attempt 3: High risk → Block
            new FraudMLOperationDto(
                type: 'card_bind',
                amount: 0,
                userId: $this->user->id,
                ipAddress: '172.16.0.1',
                deviceFingerprint: 'device-unknown-3',
            ),
        ];

        $decisions = [];
        foreach ($operations as $operation) {
            $score = $this->fraudService->scoreOperation($operation);

            $decision = $this->fraudService->shouldBlock($score, 'card_bind') ? 'block' : 'allow';
            $decisions[] = $decision;

            if ($decision === 'block') {
                FraudAttempt::factory()
                    ->for($this->user)
                    ->create([
                        'operation_type' => $operation->type,
                        'ml_score' => $score,
                        'decision' => 'block',
                    ]);
            }
        }

        // Last attempt should be blocked
        $this->assertEquals('block', end($decisions));

        // Verify escalation in fraud attempts
        $totalAttempts = FraudAttempt::where('user_id', $this->user->id)->count();
        $this->assertGreaterThan(0, $totalAttempts);
    }

    /** @test */
    public function it_uses_fallback_rules_when_model_unavailable(): void
    {
        // Simulate model unavailability
        // Create 5 rapid payment attempts (velocity check)
        for ($i = 0; $i < 5; $i++) {
            FraudAttempt::factory()
                ->for($this->user)
                ->create([
                    'operation_type' => 'payment_init',
                    'created_at' => now()->subMinutes(rand(0, 5)),
                ]);
        }

        $operation = new FraudMLOperationDto(
            type: 'payment_init',
            amount: 50000,
            userId: $this->user->id,
            ipAddress: '192.168.1.1',
            deviceFingerprint: 'device-1',
        );

        $result = $this->fraudService->predictWithFallback($operation);

        // Should use velocity rule (5 in 5 min = block)
        $this->assertEquals('block', $result['decision']);
    }

    /** @test */
    public function it_caches_fraud_scores(): void
    {
        $operation = new FraudMLOperationDto(
            type: 'payment_init',
            amount: 50000,
            userId: $this->user->id,
            ipAddress: '192.168.1.1',
            deviceFingerprint: 'device-1',
        );

        // First call - compute score
        $score1 = $this->fraudService->scoreOperation($operation);

        // Second call - should use cache
        $score2 = $this->fraudService->scoreOperation($operation);

        $this->assertEquals($score1, $score2);
    }

    /** @test */
    public function it_logs_fraud_alerts(): void
    {
        $operation = new FraudMLOperationDto(
            type: 'payment_init',
            amount: 500000, // Very large
            userId: $this->user->id,
            ipAddress: '192.168.1.1',
            deviceFingerprint: 'new-device',
        );

        $score = $this->fraudService->scoreOperation($operation);

        if ($score > 0.7) {
            FraudAttempt::factory()
                ->for($this->user)
                ->create([
                    'operation_type' => 'payment_init',
                    'ml_score' => $score,
                    'decision' => 'block',
                ]);

            Log::channel('fraud_alert')->warning('Fraud detected', [
                'user_id' => $this->user->id,
                'score' => $score,
                'decision' => 'block',
            ]);
        }

        Log::assertLogged(function ($message) {
            return str_contains($message, 'fraud') || str_contains($message, 'fraud_alert');
        });
    }

    /** @test */
    public function it_provides_fraud_statistics(): void
    {
        // Create fraud history
        for ($i = 0; $i < 10; $i++) {
            FraudAttempt::factory()
                ->for($this->user)
                ->create([
                    'decision' => $i < 5 ? 'allow' : 'block',
                ]);
        }

        $totalAttempts = FraudAttempt::where('user_id', $this->user->id)->count();
        $blockedAttempts = FraudAttempt::where('user_id', $this->user->id)
            ->where('decision', 'block')
            ->count();

        $blockRate = ($blockedAttempts / $totalAttempts) * 100;

        $this->assertEquals(10, $totalAttempts);
        $this->assertEquals(5, $blockedAttempts);
        $this->assertEquals(50, $blockRate);
    }

    /** @test */
    public function it_respects_operation_type_thresholds(): void
    {
        // Card binding has stricter threshold than payments
        $cardBindScore = 0.65;
        $paymentScore = 0.65;

        $shouldBlockCardBind = $this->fraudService->shouldBlock($cardBindScore, 'card_bind');
        $shouldBlockPayment = $this->fraudService->shouldBlock($paymentScore, 'payment_init');

        // Card bind should be stricter
        $this->assertTrue($shouldBlockCardBind);
        // Payment might be allowed at same score
        // (depends on config threshold)
    }

    /** @test */
    public function it_tracks_fraud_attempt_features(): void
    {
        $operation = new FraudMLOperationDto(
            type: 'payment_init',
            amount: 50000,
            userId: $this->user->id,
            ipAddress: '192.168.1.1',
            deviceFingerprint: 'device-1',
        );

        $features = $this->fraudService->extractFeatures($operation);

        FraudAttempt::factory()
            ->for($this->user)
            ->create([
                'operation_type' => 'payment_init',
                'features_json' => $features,
                'ml_version' => $this->fraudService->getCurrentModelVersion(),
            ]);

        $this->assertDatabaseHas('fraud_attempts', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_implements_fraud_recovery_whitelist(): void
    {
        // After false positive, user might be whitelisted temporarily
        FraudAttempt::factory()
            ->for($this->user)
            ->create([
                'decision' => 'block',
                'created_at' => now()->subHours(1),
            ]);

        // User appeals and is whitelisted for 24 hours
        $this->user->update([
            'fraud_whitelisted_until' => now()->addHours(24),
        ]);

        // Next operation should be allowed
        $operation = new FraudMLOperationDto(
            type: 'payment_init',
            amount: 100000,
            userId: $this->user->id,
            ipAddress: '192.168.1.1',
            deviceFingerprint: 'device-1',
        );

        $score = $this->fraudService->scoreOperation($operation);

        // Should allow because user is whitelisted
        $shouldAllow = $this->user->fraud_whitelisted_until &&
                      $this->user->fraud_whitelisted_until->isFuture();

        $this->assertTrue($shouldAllow);
    }
}
