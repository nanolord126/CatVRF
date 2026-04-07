<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\Gateways;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\Enums\PaymentProvider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Фабрика выбора платёжного шлюза.
 *
 * Инстанцирует нужный класс через DI-контейнер на основании выбранного провайдера.
 * Используется PaymentService для получения конкретной реализации шлюза.
 *
 * @see PaymentGatewayInterface
 * @package App\Domains\Payment\Services\Gateways
 */
final readonly class PaymentGatewayFactory
{
    /** @var array<string, class-string<PaymentGatewayInterface>> Реестр провайдеров → классов шлюзов */
    private const GATEWAY_MAP = [
        'tinkoff' => TinkoffGateway::class,
        'sber'    => SberGateway::class,
        'tochka'  => TochkaGateway::class,
    ];

    public function __construct(
        private Container $container,
        private LoggerInterface $logger,
    ) {}

    /**
     * Создать инстанс шлюза для выбранного провайдера.
     *
     * @param PaymentProvider $provider Enum провайдера (TINKOFF, SBER, TOCHKA)
     * @return PaymentGatewayInterface Реализация шлюза, готовая к использованию
     *
     * @throws InvalidArgumentException Если провайдер не поддерживается
     */
    public function make(PaymentProvider $provider): PaymentGatewayInterface
    {
        if (!$this->supports($provider)) {
            throw new InvalidArgumentException("Gateway for provider {$provider->value} is not implemented.");
        }

        $gateway = match ($provider) {
            PaymentProvider::TINKOFF => $this->container->make(TinkoffGateway::class),
            PaymentProvider::SBER    => $this->container->make(SberGateway::class),
            PaymentProvider::TOCHKA  => $this->container->make(TochkaGateway::class),
        };

        $this->logger->info('Payment gateway resolved', [
            'provider' => $provider->value,
            'gateway_class' => $gateway::class,
        ]);

        return $gateway;
    }

    /**
     * Проверить, поддерживается ли данный провайдер фабрикой.
     *
     * @param PaymentProvider $provider Провайдер для проверки
     * @return bool true если провайдер реализован
     */
    public function supports(PaymentProvider $provider): bool
    {
        return array_key_exists($provider->value, self::GATEWAY_MAP);
    }

    /**
     * Получить список всех поддерживаемых провайдеров.
     *
     * @return array<int, string> Массив кодов провайдеров
     */
    public function availableProviders(): array
    {
        return array_keys(self::GATEWAY_MAP);
    }
}
