<?php declare(strict_types=1);

namespace App\Services;



use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager as LaravelLogManager;

/**
 * Class LogManager
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Services
 */
final readonly class LogManager
{
    public function __construct(
        private readonly Request $request,
        private readonly LaravelLogManager $logger,
    ) {}


    // Dependencies injected via constructor
        // Add private readonly properties here
        public function info(string $message, array $context = []): void
        {
            $this->logger->info($message, array_merge($context, ['correlation_id' => $this->request?->header('X-Correlation-ID') ?? '']));
        }

        public function warn(string $message, array $context = []): void
        {
            $this->logger->warning($message, array_merge($context, ['correlation_id' => $this->request?->header('X-Correlation-ID') ?? '']));
        }

        public function warning(string $message, array $context = []): void
        {
            $this->logger->warning($message, array_merge($context, ['correlation_id' => $this->request?->header('X-Correlation-ID') ?? '']));
        }

        public function error(string $message, array $context = []): void
        {
            $this->logger->error($message, array_merge($context, ['correlation_id' => $this->request?->header('X-Correlation-ID') ?? '']));
        }

        public function debug(string $message, array $context = []): void
        {
            $this->logger->debug($message, array_merge($context, ['correlation_id' => $this->request?->header('X-Correlation-ID') ?? '']));
        }

        public function channel(string $channel): static
        {
            return $this;
        }
}
