<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Enums;

use App\Domains\Inventory\Enums\InventoryCheckStatus;
use App\Domains\Inventory\Enums\StockMovementType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Тесты Layer (Enums): StockMovementType, InventoryCheckStatus.
 */
#[CoversClass(StockMovementType::class)]
#[CoversClass(InventoryCheckStatus::class)]
final class InventoryEnumsTest extends TestCase
{
    /* ================================================================== */
    /*  StockMovementType                                                  */
    /* ================================================================== */

    #[Test]
    public function stock_movement_type_has_six_cases(): void
    {
        self::assertCount(6, StockMovementType::cases());
    }

    #[Test]
    public function stock_movement_type_values_match(): void
    {
        self::assertSame('in', StockMovementType::IN->value);
        self::assertSame('out', StockMovementType::OUT->value);
        self::assertSame('reserve', StockMovementType::RESERVE->value);
        self::assertSame('release', StockMovementType::RELEASE->value);
        self::assertSame('return', StockMovementType::RETURN->value);
        self::assertSame('adjustment', StockMovementType::ADJUSTMENT->value);
    }

    /** @return list<array{StockMovementType, bool}> */
    public static function incrementProvider(): array
    {
        return [
            [StockMovementType::IN, true],
            [StockMovementType::RELEASE, true],
            [StockMovementType::RETURN, true],
            [StockMovementType::OUT, false],
            [StockMovementType::RESERVE, false],
            [StockMovementType::ADJUSTMENT, false],
        ];
    }

    #[Test]
    #[DataProvider('incrementProvider')]
    public function stock_movement_type_is_increment(StockMovementType $type, bool $expected): void
    {
        self::assertSame($expected, $type->isIncrement());
    }

    /** @return list<array{StockMovementType, bool}> */
    public static function decrementProvider(): array
    {
        return [
            [StockMovementType::OUT, true],
            [StockMovementType::RESERVE, true],
            [StockMovementType::IN, false],
            [StockMovementType::RELEASE, false],
            [StockMovementType::RETURN, false],
            [StockMovementType::ADJUSTMENT, false],
        ];
    }

    #[Test]
    #[DataProvider('decrementProvider')]
    public function stock_movement_type_is_decrement(StockMovementType $type, bool $expected): void
    {
        self::assertSame($expected, $type->isDecrement());
    }

    #[Test]
    public function stock_movement_type_values_returns_all_strings(): void
    {
        $values = StockMovementType::values();
        self::assertCount(6, $values);
        self::assertContains('in', $values);
        self::assertContains('out', $values);
        self::assertContains('reserve', $values);
        self::assertContains('release', $values);
        self::assertContains('return', $values);
        self::assertContains('adjustment', $values);
    }

    /* ================================================================== */
    /*  InventoryCheckStatus                                               */
    /* ================================================================== */

    #[Test]
    public function inventory_check_status_has_four_cases(): void
    {
        self::assertCount(4, InventoryCheckStatus::cases());
    }

    #[Test]
    public function inventory_check_status_values_match(): void
    {
        self::assertSame('planned', InventoryCheckStatus::PLANNED->value);
        self::assertSame('in_progress', InventoryCheckStatus::IN_PROGRESS->value);
        self::assertSame('completed', InventoryCheckStatus::COMPLETED->value);
        self::assertSame('discrepancy', InventoryCheckStatus::DISCREPANCY->value);
    }

    /** @return list<array{InventoryCheckStatus, bool}> */
    public static function terminalProvider(): array
    {
        return [
            [InventoryCheckStatus::PLANNED, false],
            [InventoryCheckStatus::IN_PROGRESS, false],
            [InventoryCheckStatus::COMPLETED, true],
            [InventoryCheckStatus::DISCREPANCY, true],
        ];
    }

    #[Test]
    #[DataProvider('terminalProvider')]
    public function inventory_check_status_is_terminal(InventoryCheckStatus $status, bool $expected): void
    {
        self::assertSame($expected, $status->isTerminal());
    }

    #[Test]
    public function planned_can_transition_to_in_progress_only(): void
    {
        $transitions = InventoryCheckStatus::PLANNED->allowedTransitions();
        self::assertCount(1, $transitions);
        self::assertSame(InventoryCheckStatus::IN_PROGRESS, $transitions[0]);
    }

    #[Test]
    public function in_progress_can_transition_to_completed_or_discrepancy(): void
    {
        $transitions = InventoryCheckStatus::IN_PROGRESS->allowedTransitions();
        self::assertCount(2, $transitions);
        self::assertContains(InventoryCheckStatus::COMPLETED, $transitions);
        self::assertContains(InventoryCheckStatus::DISCREPANCY, $transitions);
    }

    #[Test]
    public function completed_has_no_transitions(): void
    {
        self::assertCount(0, InventoryCheckStatus::COMPLETED->allowedTransitions());
    }

    #[Test]
    public function discrepancy_has_no_transitions(): void
    {
        self::assertCount(0, InventoryCheckStatus::DISCREPANCY->allowedTransitions());
    }

    #[Test]
    public function inventory_check_status_values_returns_all_strings(): void
    {
        $values = InventoryCheckStatus::values();
        self::assertCount(4, $values);
        self::assertContains('planned', $values);
        self::assertContains('in_progress', $values);
        self::assertContains('completed', $values);
        self::assertContains('discrepancy', $values);
    }
}
