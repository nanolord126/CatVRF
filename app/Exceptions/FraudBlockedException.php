<?php declare(strict_types=1);

namespace App\Exceptions;


use Illuminate\Contracts\Routing\ResponseFactory;
use RuntimeException;

/**
 * Выбрасывается FraudControlService когда операция заблокирована.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
/**
 * Class FraudBlockedException
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
final class FraudBlockedException extends RuntimeException
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        string              $message       = 'Operation blocked by fraud control',
        private string $correlationId = '',
        int                 $code          = 423,
        ?\Throwable         $previous      = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return $this->responseFactory->json([
            'error'          => 'fraud_blocked',
            'message'        => $this->getMessage(),
            'correlation_id' => $this->correlationId,
        ], 423);
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
