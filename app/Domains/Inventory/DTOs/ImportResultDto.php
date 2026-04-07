<?php

declare(strict_types=1);

namespace App\Domains\Inventory\DTOs;

/**
 * DTO результата импорта товаров на склад.
 *
 * Содержит счётчики успешных/пропущенных/ошибочных строк.
 */
final readonly class ImportResultDto
{
    /**
     * @param int                $totalRows     Всего строк
     * @param int                $imported      Успешно импортировано
     * @param int                $skipped       Пропущено (дубликаты)
     * @param list<string>       $errors        Описания ошибок
     * @param string             $correlationId ID корреляции
     */
    public function __construct(
        public int    $totalRows,
        public int    $imported,
        public int    $skipped,
        public array  $errors,
        public string $correlationId,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function failedCount(): int
    {
        return $this->totalRows - $this->imported - $this->skipped;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'total_rows'     => $this->totalRows,
            'imported'       => $this->imported,
            'skipped'        => $this->skipped,
            'failed'         => $this->failedCount(),
            'errors'         => $this->errors,
            'correlation_id' => $this->correlationId,
        ];
    }
}
