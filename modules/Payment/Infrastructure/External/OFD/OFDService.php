<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External\OFD;

use Modules\Payment\Domain\Contracts\OFDInterface;

/**
 * OFD Service Factory - selects appropriate OFD provider.
 */
final readonly class OFDService
{
    private const PROVIDER_MAP = [
        'kontur' => KonturOFDService::class,
        'tensor' => TensorOFDService::class,
    ];

    public function __construct(
        private readonly string $defaultProvider = 'tensor',
    ) {}

    public function make(string $provider): OFDInterface
    {
        $class = self::PROVIDER_MAP[$provider] ?? self::PROVIDER_MAP[$this->defaultProvider];
        
        return app($class);
    }

    public function availableProviders(): array
    {
        return array_keys(self::PROVIDER_MAP);
    }
}
