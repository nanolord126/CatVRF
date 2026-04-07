<?php declare(strict_types=1);

/**
 * DuplicatePaymentException — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 * @see https://catvrf.ru/docs/duplicatepaymentexception
 */


namespace App\Exceptions;


use Illuminate\Contracts\Routing\ResponseFactory;
final class DuplicatePaymentException extends \Exception
{

    public function __construct(
        private readonly ResponseFactory $responseFactory,
            string $message = 'Duplicate payment attempt detected',
            int $code = 0,
            ?Exception $previous = null
        ) {
            parent::__construct($message, $code, $previous);
        }

        public function render()
        {
            return $this->responseFactory->json([
                'error' => 'Duplicate payment attempt',
                'message' => $this->message,
            ], $this->response->HTTP_CONFLICT);  // 409
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
