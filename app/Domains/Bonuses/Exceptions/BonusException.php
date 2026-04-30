<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Exceptions;

use RuntimeException;
use Throwable as BaseThrowable;

/**
 * Базовое исключение для всех бизнес-ошибок бонусной системы.
 *
 * Используется для domain-specific ошибок, которые должны быть обработаны
 * на уровне приложения и возвращены пользователю в понятном виде.
 */
abstract class BonusException extends RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?BaseThrowable $previous = null,
        public readonly ?string $correlationId = null,
        public readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Получить тип ошибки для API ответа.
     */
    abstract public function getErrorType(): string;

    /**
     * Получить HTTP статус код для ошибки.
     */
    public function getHttpStatusCode(): int
    {
        return 400;
    }

    /**
     * Получить детализированный контекст для логирования.
     */
    public function getContext(): array
    {
        return array_merge($this->context, [
            'correlation_id' => $this->correlationId,
            'exception_class' => static::class,
        ]);
    }
}
