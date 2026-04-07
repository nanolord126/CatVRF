<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Enums;

use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для PaymentStatus и PaymentProvider enums.
 */
final class PaymentEnumsTest extends TestCase
{
    // ─── PaymentStatus ───────────────────────────────────────────

    public function test_payment_status_has_six_cases(): void
    {
        $this->assertCount(6, PaymentStatus::cases());
    }

    public function test_payment_status_values(): void
    {
        $this->assertSame('pending', PaymentStatus::PENDING->value);
        $this->assertSame('authorized', PaymentStatus::AUTHORIZED->value);
        $this->assertSame('captured', PaymentStatus::CAPTURED->value);
        $this->assertSame('refunded', PaymentStatus::REFUNDED->value);
        $this->assertSame('failed', PaymentStatus::FAILED->value);
        $this->assertSame('cancelled', PaymentStatus::CANCELLED->value);
    }

    public function test_payment_status_labels_are_strings(): void
    {
        foreach (PaymentStatus::cases() as $status) {
            $label = $status->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_payment_status_colors_are_strings(): void
    {
        foreach (PaymentStatus::cases() as $status) {
            $color = $status->color();
            $this->assertIsString($color);
            $this->assertNotEmpty($color);
        }
    }

    public function test_payment_status_final_statuses(): void
    {
        $this->assertFalse(PaymentStatus::PENDING->isFinal());
        $this->assertFalse(PaymentStatus::AUTHORIZED->isFinal());
        $this->assertTrue(PaymentStatus::CAPTURED->isFinal());
        $this->assertTrue(PaymentStatus::REFUNDED->isFinal());
        $this->assertTrue(PaymentStatus::FAILED->isFinal());
        $this->assertTrue(PaymentStatus::CANCELLED->isFinal());
    }

    public function test_payment_status_allowed_transitions_from_pending(): void
    {
        $transitions = PaymentStatus::PENDING->allowedTransitions();
        $this->assertContains(PaymentStatus::AUTHORIZED, $transitions);
        $this->assertContains(PaymentStatus::FAILED, $transitions);
        $this->assertContains(PaymentStatus::CANCELLED, $transitions);
        $this->assertNotContains(PaymentStatus::CAPTURED, $transitions);
    }

    public function test_payment_status_allowed_transitions_from_authorized(): void
    {
        $transitions = PaymentStatus::AUTHORIZED->allowedTransitions();
        $this->assertContains(PaymentStatus::CAPTURED, $transitions);
        $this->assertContains(PaymentStatus::CANCELLED, $transitions);
        $this->assertNotContains(PaymentStatus::PENDING, $transitions);
    }

    public function test_payment_status_no_transitions_from_terminal(): void
    {
        // REFUNDED, FAILED, CANCELLED — терминальные (нет переходов)
        $this->assertEmpty(PaymentStatus::REFUNDED->allowedTransitions());
        $this->assertEmpty(PaymentStatus::FAILED->allowedTransitions());
        $this->assertEmpty(PaymentStatus::CANCELLED->allowedTransitions());

        // CAPTURED — финальный по isFinal(), но может перейти в REFUNDED
        $this->assertNotEmpty(PaymentStatus::CAPTURED->allowedTransitions());
        $this->assertContains(PaymentStatus::REFUNDED, PaymentStatus::CAPTURED->allowedTransitions());
    }

    public function test_can_transition_to(): void
    {
        $this->assertTrue(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::AUTHORIZED));
        $this->assertFalse(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::CAPTURED));
        $this->assertTrue(PaymentStatus::CAPTURED->canTransitionTo(PaymentStatus::REFUNDED));
        $this->assertFalse(PaymentStatus::FAILED->canTransitionTo(PaymentStatus::PENDING));
    }

    public function test_payment_status_from_string(): void
    {
        $this->assertSame(PaymentStatus::PENDING, PaymentStatus::from('pending'));
        $this->assertSame(PaymentStatus::CAPTURED, PaymentStatus::from('captured'));
    }

    // ─── PaymentProvider ─────────────────────────────────────────

    public function test_payment_provider_has_six_cases(): void
    {
        $this->assertCount(6, PaymentProvider::cases());
    }

    public function test_payment_provider_values(): void
    {
        $this->assertSame('tinkoff', PaymentProvider::TINKOFF->value);
        $this->assertSame('sber', PaymentProvider::SBER->value);
        $this->assertSame('tochka', PaymentProvider::TOCHKA->value);
        $this->assertSame('sbp', PaymentProvider::SBP->value);
        $this->assertSame('yookassa', PaymentProvider::YOOKASSA->value);
        $this->assertSame('manual', PaymentProvider::MANUAL->value);
    }

    public function test_payment_provider_labels_are_strings(): void
    {
        foreach (PaymentProvider::cases() as $provider) {
            $label = $provider->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_supports_two_phase(): void
    {
        $this->assertTrue(PaymentProvider::TINKOFF->supportsTwoPhase());
        $this->assertTrue(PaymentProvider::SBER->supportsTwoPhase());
        $this->assertTrue(PaymentProvider::YOOKASSA->supportsTwoPhase());
        $this->assertFalse(PaymentProvider::TOCHKA->supportsTwoPhase());
        $this->assertFalse(PaymentProvider::SBP->supportsTwoPhase());
        $this->assertFalse(PaymentProvider::MANUAL->supportsTwoPhase());
    }

    public function test_supports_refund(): void
    {
        $this->assertTrue(PaymentProvider::TINKOFF->supportsRefund());
        $this->assertTrue(PaymentProvider::SBER->supportsRefund());
        $this->assertFalse(PaymentProvider::MANUAL->supportsRefund());
    }

    public function test_capture_timeout_is_positive(): void
    {
        foreach (PaymentProvider::cases() as $provider) {
            $timeout = $provider->captureTimeoutMinutes();
            $this->assertIsInt($timeout);
            $this->assertGreaterThan(0, $timeout);
        }
    }

    public function test_payment_provider_from_string(): void
    {
        $this->assertSame(PaymentProvider::TINKOFF, PaymentProvider::from('tinkoff'));
        $this->assertSame(PaymentProvider::SBP, PaymentProvider::from('sbp'));
    }
}
