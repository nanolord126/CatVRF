<?php declare(strict_types=1);

namespace Tests\Feature\Api\Controllers;

use App\Models\PromoCampaign;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FraudControlService;
use App\Services\Marketing\PromoCampaignService;
use App\Services\Security\RateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * PromoControllerTest — тесты API V1 промо-контроллера.
 * Проверяет: применение промо, rate-limit, fraud-block, аудит-лог, валидацию.
 */
final class PromoControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);
        app()->bind('tenant', fn () => $this->tenant);
    }

    /** @test */
    public function it_blocks_request_when_fraud_detected(): void
    {
        $fraudMock = $this->createMock(FraudControlService::class);
        $fraudMock->method('check')->willReturn([
            'score'    => 0.95,
            'decision' => 'block',
        ]);
        $this->app->instance(FraudControlService::class, $fraudMock);

        $rateMock = $this->createMock(RateLimiterService::class);
        $rateMock->method('checkPromoApply')->willReturn(true);
        $this->app->instance(RateLimiterService::class, $rateMock);

        Log::shouldReceive('channel')->with('fraud_alert')->once()->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $this->actingAs($this->user)
            ->postJson('/api/v1/promo/apply', [
                'code'      => 'TEST10',
                'amount'    => 10000,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['correlation_id']);
    }

    /** @test */
    public function it_returns_429_when_rate_limit_exceeded(): void
    {
        $fraudMock = $this->createMock(FraudControlService::class);
        $fraudMock->method('check')->willReturn(['score' => 0.1, 'decision' => 'allow']);
        $this->app->instance(FraudControlService::class, $fraudMock);

        $rateMock = $this->createMock(RateLimiterService::class);
        $rateMock->method('checkPromoApply')->willReturn(false);
        $this->app->instance(RateLimiterService::class, $rateMock);

        $this->actingAs($this->user)
            ->postJson('/api/v1/promo/apply', [
                'code'      => 'TEST10',
                'amount'    => 10000,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_applies_promo_successfully(): void
    {
        $fraudMock = $this->createMock(FraudControlService::class);
        $fraudMock->method('check')->willReturn(['score' => 0.1, 'decision' => 'allow']);
        $this->app->instance(FraudControlService::class, $fraudMock);

        $rateMock = $this->createMock(RateLimiterService::class);
        $rateMock->method('checkPromoApply')->willReturn(true);
        $this->app->instance(RateLimiterService::class, $rateMock);

        $promoMock = $this->createMock(PromoCampaignService::class);
        $promoMock->method('applyPromo')->willReturn([
            'success'      => true,
            'discount'     => 1000,
            'final_amount' => 9000,
        ]);
        $this->app->instance(PromoCampaignService::class, $promoMock);

        Log::shouldReceive('channel')->with('audit')->twice()->andReturnSelf();
        Log::shouldReceive('info')->twice();

        $this->actingAs($this->user)
            ->postJson('/api/v1/promo/apply', [
                'code'      => 'PROMO10',
                'amount'    => 10000,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('discount', 1000)
            ->assertJsonStructure(['correlation_id', 'final_amount']);
    }

    /** @test */
    public function it_returns_422_for_invalid_promo_code(): void
    {
        $fraudMock = $this->createMock(FraudControlService::class);
        $fraudMock->method('check')->willReturn(['score' => 0.1, 'decision' => 'allow']);
        $this->app->instance(FraudControlService::class, $fraudMock);

        $rateMock = $this->createMock(RateLimiterService::class);
        $rateMock->method('checkPromoApply')->willReturn(true);
        $this->app->instance(RateLimiterService::class, $rateMock);

        $promoMock = $this->createMock(PromoCampaignService::class);
        $promoMock->method('applyPromo')->willReturn([
            'success' => false,
            'error'   => 'Invalid or exhausted promo code',
        ]);
        $this->app->instance(PromoCampaignService::class, $promoMock);

        $this->actingAs($this->user)
            ->postJson('/api/v1/promo/apply', [
                'code'      => 'INVALID',
                'amount'    => 10000,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['error', 'correlation_id']);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/promo/apply', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function apply_logs_to_audit_channel(): void
    {
        $fraudMock = $this->createMock(FraudControlService::class);
        $fraudMock->method('check')->willReturn(['score' => 0.1, 'decision' => 'allow']);
        $this->app->instance(FraudControlService::class, $fraudMock);

        $rateMock = $this->createMock(RateLimiterService::class);
        $rateMock->method('checkPromoApply')->willReturn(true);
        $this->app->instance(RateLimiterService::class, $rateMock);

        $promoMock = $this->createMock(PromoCampaignService::class);
        $promoMock->method('applyPromo')->willReturn(['success' => true, 'discount' => 500]);
        $this->app->instance(PromoCampaignService::class, $promoMock);

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->once()->with(\Mockery::type('string'), \Mockery::on(fn($ctx) => isset($ctx['correlation_id'])));

        $this->actingAs($this->user)
            ->postJson('/api/v1/promo/apply', [
                'code'      => 'CODE50',
                'amount'    => 5000,
                'tenant_id' => $this->tenant->id,
            ]);
    }

    /** @test */
    public function index_returns_active_campaigns(): void
    {
        PromoCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'active',
            'start_at'  => now()->subDay(),
            'end_at'    => now()->addDay(),
        ]);

        PromoCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'expired',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/promo?tenant_id=' . $this->tenant->id)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'correlation_id']);
    }
}
