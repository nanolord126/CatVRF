<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BonusService.
 *
 * @covers \App\Domains\Finances\Domain\Services\BonusService
 */
final class BonusServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Domains\Finances\Domain\Services\BonusService::class);
        $this->assertTrue($reflection->isFinal(), 'BonusService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'BonusService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Domains\Finances\Domain\Services\BonusService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_awardBonus_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Finances\Domain\Services\BonusService::class, 'awardBonus'),
            'BonusService must implement awardBonus()'
        );
    }

    public function test_unlockExpiredHolds_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Finances\Domain\Services\BonusService::class, 'unlockExpiredHolds'),
            'BonusService must implement unlockExpiredHolds()'
        );
    }

    public function test_spendBonuses_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Finances\Domain\Services\BonusService::class, 'spendBonuses'),
            'BonusService must implement spendBonuses()'
        );
    }

    public function test_getAvailableBonusBalance_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Finances\Domain\Services\BonusService::class, 'getAvailableBonusBalance'),
            'BonusService must implement getAvailableBonusBalance()'
        );
    }

    public function test_getHistory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Finances\Domain\Services\BonusService::class, 'getHistory'),
            'BonusService must implement getHistory()'
        );
    }

    public function test_expireOldBonuses_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Finances\Domain\Services\BonusService::class, 'expireOldBonuses'),
            'BonusService must implement expireOldBonuses()'
        );
    }

}
