<?php
declare(strict_types=1);

namespace Tests\Unit\Services\Wallet;

use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use Tests\SimpleTestCase;

/**
 * Unit Tests для WalletService (PHPUnit формат)
 */
class WalletServiceTest extends SimpleTestCase
{
    protected WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WalletService();
    }

    /** @test */
    public function test_wallet_service_can_create_wallet(): void
    {
        $wallet = $this->service->createWallet(
            tenantId: 1,
            userId: 1,
            initialBalance: 50000
        );

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(50000, $wallet->current_balance);
        $this->assertNotNull($wallet->uuid);
        $this->assertNotNull($wallet->correlation_id);
    }

    /** @test */
    public function test_wallet_credit_operation(): void
    {
        $wallet = Wallet::factory()->create(['current_balance' => 10000]);

        $result = $this->service->credit(
            walletId: $wallet->id,
            amount: 5000,
            reason: 'bonus',
            correlationId: $this->correlationId
        );

        $this->assertTrue($result);
        $wallet->refresh();
        $this->assertEquals(15000, $wallet->current_balance);
    }

    /** @test */
    public function test_wallet_debit_operation(): void
    {
        $wallet = Wallet::factory()->create(['current_balance' => 10000]);

        $result = $this->service->debit(
            walletId: $wallet->id,
            amount: 3000,
            reason: 'purchase',
            correlationId: $this->correlationId
        );

        $this->assertTrue($result);
        $wallet->refresh();
        $this->assertEquals(7000, $wallet->current_balance);
    }

    /** @test */
    public function test_wallet_hold_operation(): void
    {
        $wallet = Wallet::factory()->create(['current_balance' => 10000, 'hold_amount' => 0]);

        $result = $this->service->hold(
            walletId: $wallet->id,
            amount: 2000,
            correlationId: $this->correlationId
        );

        $this->assertTrue($result);
        $wallet->refresh();
        $this->assertEquals(2000, $wallet->hold_amount);
    }

    /** @test */
    public function test_wallet_release_operation(): void
    {
        $wallet = Wallet::factory()->create(['current_balance' => 10000, 'hold_amount' => 2000]);

        $result = $this->service->release(
            walletId: $wallet->id,
            amount: 2000,
            correlationId: $this->correlationId
        );

        $this->assertTrue($result);
        $wallet->refresh();
        $this->assertEquals(0, $wallet->hold_amount);
    }
}
