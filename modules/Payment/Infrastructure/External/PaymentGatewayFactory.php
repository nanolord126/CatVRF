<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use Modules\Payment\Domain\ValueObjects\PaymentProvider;
use Psr\Log\LoggerInterface;

/**
 * Factory for selecting payment gateway.
 *
 * Instantiates the appropriate class via DI container based on selected provider.
 * Used by PaymentService to get specific gateway implementation.
 */
final readonly class PaymentGatewayFactory
{
    /** @var array<string, class-string<PaymentGatewayInterface>> Registry of providers → gateway classes */
    private const GATEWAY_MAP = [
        'tinkoff' => TinkoffGateway::class,
        'sber' => SberGateway::class,
        'tochka' => TochkaGateway::class,
        'sbp' => SBPGateway::class,
        'yookassa' => YookassaGateway::class,
    ];

    public function __construct(
        private readonly Container $container,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Create gateway instance for selected provider.
     *
     * @param PaymentProvider $provider Provider enum (TINKOFF, SBER, TOCHKA)
     * @return PaymentGatewayInterface Gateway implementation ready for use
     * @throws InvalidArgumentException If provider is not supported
     */
    public function make(PaymentProvider $provider): PaymentGatewayInterface
    {
        if (! $this->supports($provider)) {
            throw new InvalidArgumentException("Gateway for provider {$provider->value} is not implemented.");
        }

        $gateway = match ($provider) {
            PaymentProvider::TINKOFF => $this->container->make(TinkoffGateway::class),
            PaymentProvider::SBER => $this->container->make(SberGateway::class),
            PaymentProvider::TOCHKA => $this->container->make(TochkaGateway::class),
            PaymentProvider::SBP => $this->container->make(SBPGateway::class),
            PaymentProvider::YOOKASSA => $this->container->make(YookassaGateway::class),
            default => throw new InvalidArgumentException("Gateway for provider {$provider->value} is not implemented."),
        };

        $this->logger->info('Payment gateway resolved', [
            'provider' => $provider->value,
            'gateway_class' => $gateway::class,
        ]);

        return $gateway;
    }

    /**
     * Check if provider is supported by factory.
     *
     * @param PaymentProvider $provider Provider to check
     * @return bool true if provider is implemented
     */
    public function supports(PaymentProvider $provider): bool
    {
        return array_key_exists($provider->value, self::GATEWAY_MAP);
    }

    /**
     * Get list of all supported providers.
     *
     * @return array<int, string> Array of provider codes
     */
    public function availableProviders(): array
    {
        return array_keys(self::GATEWAY_MAP);
    }
}
