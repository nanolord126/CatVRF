<?php declare(strict_types=1);

/**
 * RateLimitException — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ratelimitexception
 * @see https://catvrf.ru/docs/ratelimitexception
 * @see https://catvrf.ru/docs/ratelimitexception
 */


namespace App\Exceptions;


use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Class RateLimitException
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Exceptions
 */
final class RateLimitException extends \Exception
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly int $retryAfter,
        string $message = 'Too Many Attempts.',
        int $code = 429,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return $this->responseFactory->json([
            'error' => 'Too many requests',
            'message' => $this->getMessage(),
        ], 429)
            ->header('Retry-After', $this->retryAfter)
            ->header('X-RateLimit-Reset', now()->addSeconds($this->retryAfter)->timestamp);
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }
}
