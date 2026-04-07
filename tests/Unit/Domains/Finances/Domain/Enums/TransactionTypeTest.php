<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\Domain\Enums;

use App\Domains\Finances\Domain\Enums\TransactionType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для TransactionType enum.
 *
 * Покрытие: все кейсы, isCredit(), isDebit(), label(), color().
 */
final class TransactionTypeTest extends TestCase
{
    #[Test]
    public function it_has_all_required_cases(): void
    {
        $expected = [
            'deposit', 'withdrawal', 'commission', 'bonus',
            'refund', 'payout', 'hold', 'release_hold',
        ];
        $actual = array_map(
            static fn (TransactionType $t): string => $t->value,
            TransactionType::cases(),
        );

        self::assertSame($expected, $actual);
    }

    #[Test]
    #[DataProvider('creditTypesProvider')]
    public function credit_types_are_detected(TransactionType $type): void
    {
        self::assertTrue($type->isCredit(), "{$type->value} should be credit");
        self::assertFalse($type->isDebit(), "{$type->value} should NOT be debit");
    }

    public static function creditTypesProvider(): iterable
    {
        yield 'deposit'      => [TransactionType::DEPOSIT];
        yield 'bonus'        => [TransactionType::BONUS];
        yield 'refund'       => [TransactionType::REFUND];
        yield 'release_hold' => [TransactionType::RELEASE_HOLD];
    }

    #[Test]
    #[DataProvider('debitTypesProvider')]
    public function debit_types_are_detected(TransactionType $type): void
    {
        self::assertTrue($type->isDebit(), "{$type->value} should be debit");
        self::assertFalse($type->isCredit(), "{$type->value} should NOT be credit");
    }

    public static function debitTypesProvider(): iterable
    {
        yield 'withdrawal' => [TransactionType::WITHDRAWAL];
        yield 'commission' => [TransactionType::COMMISSION];
        yield 'payout'     => [TransactionType::PAYOUT];
        yield 'hold'       => [TransactionType::HOLD];
    }

    #[Test]
    public function every_type_is_either_credit_or_debit(): void
    {
        foreach (TransactionType::cases() as $type) {
            $isCredit = $type->isCredit();
            $isDebit = $type->isDebit();

            self::assertTrue(
                $isCredit xor $isDebit,
                "{$type->value} must be exactly one of credit/debit, got credit={$isCredit}, debit={$isDebit}",
            );
        }
    }

    #[Test]
    public function all_cases_have_non_empty_labels(): void
    {
        foreach (TransactionType::cases() as $type) {
            self::assertNotEmpty($type->label(), "Label for {$type->value} is empty");
        }
    }

    #[Test]
    public function all_cases_have_valid_colors(): void
    {
        $validColors = ['success', 'danger', 'warning', 'info', 'gray', 'primary'];

        foreach (TransactionType::cases() as $type) {
            self::assertContains(
                $type->color(),
                $validColors,
                "Color for {$type->value} is not valid",
            );
        }
    }

    #[Test]
    public function credit_types_have_success_color(): void
    {
        foreach ([TransactionType::DEPOSIT, TransactionType::BONUS, TransactionType::REFUND, TransactionType::RELEASE_HOLD] as $type) {
            self::assertSame('success', $type->color(), "{$type->value} should have success color");
        }
    }

    #[Test]
    public function debit_types_have_danger_color(): void
    {
        foreach ([TransactionType::WITHDRAWAL, TransactionType::COMMISSION, TransactionType::PAYOUT, TransactionType::HOLD] as $type) {
            self::assertSame('danger', $type->color(), "{$type->value} should have danger color");
        }
    }

    #[Test]
    public function can_be_created_from_string_value(): void
    {
        self::assertSame(TransactionType::DEPOSIT, TransactionType::from('deposit'));
        self::assertSame(TransactionType::PAYOUT, TransactionType::from('payout'));
        self::assertSame(TransactionType::HOLD, TransactionType::from('hold'));
        self::assertSame(TransactionType::RELEASE_HOLD, TransactionType::from('release_hold'));
    }

    #[Test]
    public function tryFrom_returns_null_for_invalid_value(): void
    {
        self::assertNull(TransactionType::tryFrom('invalid'));
        self::assertNull(TransactionType::tryFrom(''));
    }
}
