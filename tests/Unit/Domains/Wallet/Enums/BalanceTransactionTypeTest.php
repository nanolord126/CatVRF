<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Enums;

use App\Domains\Wallet\Enums\BalanceTransactionType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BalanceTransactionType::class)]
final class BalanceTransactionTypeTest extends TestCase
{
    public function test_all_cases_exist(): void
    {
        $cases = BalanceTransactionType::cases();
        $this->assertCount(8, $cases);

        $values = array_map(static fn (BalanceTransactionType $t): string => $t->value, $cases);
        $this->assertContains('deposit', $values);
        $this->assertContains('withdrawal', $values);
        $this->assertContains('commission', $values);
        $this->assertContains('bonus', $values);
        $this->assertContains('refund', $values);
        $this->assertContains('payout', $values);
        $this->assertContains('hold', $values);
        $this->assertContains('release_hold', $values);
    }

    #[DataProvider('creditTypesProvider')]
    public function test_is_credit_returns_true_for_credit_types(BalanceTransactionType $type): void
    {
        $this->assertTrue($type->isCredit());
        $this->assertFalse($type->isDebit());
    }

    public static function creditTypesProvider(): array
    {
        return [
            'deposit' => [BalanceTransactionType::DEPOSIT],
            'bonus' => [BalanceTransactionType::BONUS],
            'refund' => [BalanceTransactionType::REFUND],
            'release_hold' => [BalanceTransactionType::RELEASE_HOLD],
        ];
    }

    #[DataProvider('debitTypesProvider')]
    public function test_is_debit_returns_true_for_debit_types(BalanceTransactionType $type): void
    {
        $this->assertTrue($type->isDebit());
        $this->assertFalse($type->isCredit());
    }

    public static function debitTypesProvider(): array
    {
        return [
            'withdrawal' => [BalanceTransactionType::WITHDRAWAL],
            'commission' => [BalanceTransactionType::COMMISSION],
            'payout' => [BalanceTransactionType::PAYOUT],
            'hold' => [BalanceTransactionType::HOLD],
        ];
    }

    public function test_label_returns_non_empty_string(): void
    {
        foreach (BalanceTransactionType::cases() as $type) {
            $label = $type->label();
            $this->assertNotEmpty($label, "Label for {$type->value} should not be empty");
            $this->assertIsString($label);
        }
    }

    public function test_color_returns_non_empty_string(): void
    {
        foreach (BalanceTransactionType::cases() as $type) {
            $color = $type->color();
            $this->assertNotEmpty($color, "Color for {$type->value} should not be empty");
            $this->assertIsString($color);
        }
    }

    public function test_from_valid_value(): void
    {
        $type = BalanceTransactionType::from('deposit');
        $this->assertSame(BalanceTransactionType::DEPOSIT, $type);
    }

    public function test_try_from_invalid_value_returns_null(): void
    {
        $type = BalanceTransactionType::tryFrom('invalid');
        $this->assertNull($type);
    }
}
