<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\Domain\Enums;

use App\Domains\Finances\Domain\Enums\PayoutStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для PayoutStatus enum.
 *
 * Покрытие: все кейсы, isTerminal(), canTransitionTo(), label(), color().
 */
final class PayoutStatusTest extends TestCase
{
    #[Test]
    public function it_has_all_required_cases(): void
    {
        $expected = ['draft', 'pending', 'processing', 'completed', 'failed', 'cancelled'];
        $actual = array_map(
            static fn (PayoutStatus $s): string => $s->value,
            PayoutStatus::cases(),
        );

        self::assertSame($expected, $actual);
    }

    #[Test]
    #[DataProvider('terminalStatusesProvider')]
    public function terminal_statuses_are_detected_correctly(PayoutStatus $status, bool $expected): void
    {
        self::assertSame($expected, $status->isTerminal());
    }

    public static function terminalStatusesProvider(): iterable
    {
        yield 'draft is not terminal'      => [PayoutStatus::DRAFT, false];
        yield 'pending is not terminal'    => [PayoutStatus::PENDING, false];
        yield 'processing is not terminal' => [PayoutStatus::PROCESSING, false];
        yield 'completed IS terminal'      => [PayoutStatus::COMPLETED, true];
        yield 'failed IS terminal'         => [PayoutStatus::FAILED, true];
        yield 'cancelled IS terminal'      => [PayoutStatus::CANCELLED, true];
    }

    #[Test]
    #[DataProvider('validTransitionsProvider')]
    public function valid_transitions_are_allowed(PayoutStatus $from, PayoutStatus $to): void
    {
        self::assertTrue($from->canTransitionTo($to));
    }

    public static function validTransitionsProvider(): iterable
    {
        yield 'draft → pending'      => [PayoutStatus::DRAFT, PayoutStatus::PENDING];
        yield 'draft → cancelled'    => [PayoutStatus::DRAFT, PayoutStatus::CANCELLED];
        yield 'pending → processing' => [PayoutStatus::PENDING, PayoutStatus::PROCESSING];
        yield 'pending → cancelled'  => [PayoutStatus::PENDING, PayoutStatus::CANCELLED];
        yield 'pending → failed'     => [PayoutStatus::PENDING, PayoutStatus::FAILED];
        yield 'processing → completed' => [PayoutStatus::PROCESSING, PayoutStatus::COMPLETED];
        yield 'processing → failed'    => [PayoutStatus::PROCESSING, PayoutStatus::FAILED];
    }

    #[Test]
    #[DataProvider('invalidTransitionsProvider')]
    public function invalid_transitions_are_blocked(PayoutStatus $from, PayoutStatus $to): void
    {
        self::assertFalse($from->canTransitionTo($to));
    }

    public static function invalidTransitionsProvider(): iterable
    {
        yield 'draft → completed'      => [PayoutStatus::DRAFT, PayoutStatus::COMPLETED];
        yield 'draft → processing'     => [PayoutStatus::DRAFT, PayoutStatus::PROCESSING];
        yield 'pending → draft'        => [PayoutStatus::PENDING, PayoutStatus::DRAFT];
        yield 'processing → pending'   => [PayoutStatus::PROCESSING, PayoutStatus::PENDING];
        yield 'completed → anything'   => [PayoutStatus::COMPLETED, PayoutStatus::PENDING];
        yield 'failed → anything'      => [PayoutStatus::FAILED, PayoutStatus::PENDING];
        yield 'cancelled → anything'   => [PayoutStatus::CANCELLED, PayoutStatus::PENDING];
    }

    #[Test]
    public function no_transitions_from_terminal_statuses(): void
    {
        $terminals = [PayoutStatus::COMPLETED, PayoutStatus::FAILED, PayoutStatus::CANCELLED];

        foreach ($terminals as $terminal) {
            foreach (PayoutStatus::cases() as $target) {
                self::assertFalse(
                    $terminal->canTransitionTo($target),
                    "Transition from {$terminal->value} to {$target->value} should be blocked",
                );
            }
        }
    }

    #[Test]
    public function all_cases_have_labels(): void
    {
        foreach (PayoutStatus::cases() as $status) {
            self::assertNotEmpty($status->label(), "Label for {$status->value} is empty");
        }
    }

    #[Test]
    public function all_cases_have_colors(): void
    {
        $validColors = ['gray', 'warning', 'info', 'success', 'danger'];

        foreach (PayoutStatus::cases() as $status) {
            self::assertContains(
                $status->color(),
                $validColors,
                "Color for {$status->value} is not valid",
            );
        }
    }

    #[Test]
    public function completed_has_success_color(): void
    {
        self::assertSame('success', PayoutStatus::COMPLETED->color());
    }

    #[Test]
    public function failed_has_danger_color(): void
    {
        self::assertSame('danger', PayoutStatus::FAILED->color());
    }
}
