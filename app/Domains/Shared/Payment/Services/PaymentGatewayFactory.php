<?php

declare(strict_types=1);

namespace App\Domains\Shared\Payment\Services;

use App\Domains\Shared\Payment\Contracts\GatewayInterface;
use App\Domains\Shared\Payment\Gateways\TinkoffGateway;
use App\Domains\Shared\Payment\Gateways\TochkaBankGateway;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Payment Gateway Factory
 * 
 * Factory pattern implementation for selecting appropriate payment gateway
 * based on vertical, customer type (B2B/B2C), and configuration.
 * 
 * Architecture:
 * - B2C customers: Tinkoff (primary), Sber, SBP as fallbacks
 * - B2B customers: Tochka Bank (primary for corporate payments with EDO support)
 * - Vertical-specific defaults configured in config/verticals.php
 * 
 * @version 2026.1
 */
final readonly class PaymentGatewayFactory
{
    /**
     * Get appropriate gateway based on vertical and customer type.
     *
     * @param string $vertical The vertical (e.g., 'supermarket', 'restaurant')
     * @param bool $isB2B Whether this is a B2B transaction
     * @param string|null $preferredGateway Optional preferred gateway override
     * @return GatewayInterface
     * @throws InvalidArgumentException If gateway configuration is invalid
     */
    public function make(string $vertical, bool $isB2B = false, ?string $preferredGateway = null): GatewayInterface
    {
        // If specific gateway is requested, use it directly
        if ($preferredGateway !== null) {
            return $this->resolveGateway($preferredGateway);
        }

        // B2B transactions default to Tochka Bank
        if ($isB2B) {
            Log::info('Using B2B payment gateway', [
                'vertical' => $vertical,
                'gateway' => 'tochka_bank',
            ]);
            
            return $this->resolveGateway('tochka_bank');
        }

        // B2C transactions - check vertical-specific config
        $verticalConfig = config("verticals.{$vertical}.payment", []);
        $defaultGateway = $verticalConfig['default'] ?? 'tinkoff';

        Log::info('Using B2C payment gateway', [
            'vertical' => $vertical,
            'gateway' => $defaultGateway,
        ]);

        return $this->resolveGateway($defaultGateway);
    }

    /**
     * Get available payment methods for a vertical.
     *
     * @param string $vertical
     * @return array<string, string>
     */
    public function getAvailableMethods(string $vertical): array
    {
        $verticalConfig = config("verticals.{$vertical}.payment", []);
        
        return $verticalConfig['methods'] ?? [
            'card' => 'Банковская карта',
            'sbp' => 'СБП (Система быстрых платежей)',
            'sberpay' => 'СберPay',
        ];
    }

    /**
     * Check if a gateway supports recurring payments.
     *
     * @param string $gatewayName
     * @return bool
     */
    public function supportsRecurring(string $gatewayName): bool
    {
        return match ($gatewayName) {
            'tinkoff', 'tochka_bank' => true,
            default => false,
        };
    }

    /**
     * Check if a gateway supports B2B features (EDO, invoices, payment terms).
     *
     * @param string $gatewayName
     * @return bool
     */
    public function supportsB2B(string $gatewayName): bool
    {
        return match ($gatewayName) {
            'tochka_bank' => true,
            default => false,
        };
    }

    /**
     * Resolve gateway instance by name.
     *
     * @param string $gatewayName
     * @return GatewayInterface
     * @throws InvalidArgumentException
     */
    private function resolveGateway(string $gatewayName): GatewayInterface
    {
        return match ($gatewayName) {
            'tinkoff' => app(TinkoffGateway::class),
            'tochka_bank', 'tochka' => app(TochkaBankGateway::class),
            'sber' => $this->resolveSberGateway(),
            'sbp' => $this->resolveSBPGateway(),
            default => throw new InvalidArgumentException(
                "Unsupported payment gateway: {$gatewayName}. " .
                "Supported gateways: tinkoff, tochka_bank, sber, sbp"
            ),
        };
    }

    /**
     * Resolve Sber gateway (placeholder for future implementation).
     *
     * @return GatewayInterface
     */
    private function resolveSberGateway(): GatewayInterface
    {
        // TODO: Implement SberGateway when needed
        Log::warning('Sber gateway requested but not yet implemented, falling back to Tinkoff');
        return app(TinkoffGateway::class);
    }

    /**
     * Resolve SBP gateway (placeholder for future implementation).
     *
     * @return GatewayInterface
     */
    private function resolveSBPGateway(): GatewayInterface
    {
        // TODO: Implement SBPGateway when needed
        Log::warning('SBP gateway requested but not yet implemented, falling back to Tinkoff');
        return app(TinkoffGateway::class);
    }

    /**
     * Get gateway configuration for a specific gateway.
     *
     * @param string $gatewayName
     * @return array<string, mixed>
     */
    public function getGatewayConfig(string $gatewayName): array
    {
        return match ($gatewayName) {
            'tinkoff' => [
                'api_url' => config('payment.tinkoff.api_url'),
                'terminal_key' => config('payment.tinkoff.terminal_key'),
                'supports_recurring' => true,
                'supports_b2b' => false,
            ],
            'tochka_bank', 'tochka' => [
                'api_url' => config('payment.tochka.api_url'),
                'client_id' => config('payment.tochka.client_id'),
                'supports_recurring' => true,
                'supports_b2b' => true,
                'supports_edo' => true,
            ],
            'sber' => [
                'api_url' => config('payment.sber.api_url'),
                'supports_recurring' => false,
                'supports_b2b' => false,
            ],
            'sbp' => [
                'api_url' => config('payment.sbp.api_url'),
                'supports_recurring' => false,
                'supports_b2b' => false,
            ],
            default => [],
        };
    }
}
