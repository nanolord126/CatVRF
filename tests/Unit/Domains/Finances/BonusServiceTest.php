<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances;

use PHPUnit\Framework\TestCase;
use App\Domains\Finances\Domain\Services\BonusService;

/**
 * Unit tests for BonusService.
 *
 * @covers \App\Domains\Finances\Domain\Services\BonusService
 */
final class BonusServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            BonusService::class
        );
        $this->assertTrue($reflection->isFinal(), 'BonusService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            BonusService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'BonusService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            BonusService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'BonusService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_award_bonus_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BonusService::class, 'awardBonus'),
            'BonusService must implement awardBonus()'
        );
    }

    public function test_unlock_expired_holds_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BonusService::class, 'unlockExpiredHolds'),
            'BonusService must implement unlockExpiredHolds()'
        );
    }

    public function test_spend_bonuses_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BonusService::class, 'spendBonuses'),
            'BonusService must implement spendBonuses()'
        );
    }

    public function test_get_available_bonus_balance_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BonusService::class, 'getAvailableBonusBalance'),
            'BonusService must implement getAvailableBonusBalance()'
        );
    }

    public function test_get_history_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BonusService::class, 'getHistory'),
            'BonusService must implement getHistory()'
        );
    }
}
