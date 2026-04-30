<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Exceptions;

use Exception;

/**
 * ReturnPolicyViolationException - Исключение при нарушении политики возврата.
 *
 * Выбрасывается когда:
 * - Превышен срок возврата
 * - Причина возврата не разрешена для категории
 * - Отсутствуют обязательные доказательства (фото, чек)
 * - Товар с холодной цепью возвращается не по браку
 * - Нарушены другие правила политики
 */
final class ReturnPolicyViolationException extends Exception
{
    /**
     * @param array<int, array{product_name: string, sub_vertical: string, violations: array<string>}> $violations
     */
    public function __construct(
        private readonly array $violations
    ) {
        $messages = array_map(function ($violation) {
            return "{$violation['product_name']} ({$violation['sub_vertical']}): " . implode(', ', $violation['violations']);
        }, $violations);

        parent::__construct('Нарушение политики возврата: ' . implode('; ', $messages));
    }

    /**
     * Получить список нарушений.
     *
     * @return array<int, array{product_name: string, sub_vertical: string, violations: array<string>}>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * Получить нарушения в формате для API.
     */
    public function toArray(): array
    {
        return [
            'error' => 'return_policy_violation',
            'message' => $this->getMessage(),
            'violations' => $this->violations,
        ];
    }

    /**
     * Проверить, есть ли критические нарушения.
     */
    public function hasCriticalViolations(): bool
    {
        foreach ($this->violations as $violation) {
            foreach ($violation['violations'] as $reason) {
                if (str_contains($reason, 'Превышен срок') || str_contains($reason, 'не разрешена')) {
                    return true;
                }
            }
        }

        return false;
    }
}
