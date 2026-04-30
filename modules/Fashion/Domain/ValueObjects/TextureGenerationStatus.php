<?php

declare(strict_types=1);

namespace Modules\Fashion\Domain\ValueObjects;

enum TextureGenerationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case VALIDATING = 'validating';
    case RETRYING = 'retrying';

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => $status === self::PROCESSING,
            self::PROCESSING => $status === self::COMPLETED
                || $status === self::FAILED
                || $status === self::VALIDATING,
            self::VALIDATING => $status === self::COMPLETED
                || $status === self::FAILED
                || $status === self::RETRYING,
            self::RETRYING => $status === self::PROCESSING,
            self::COMPLETED => false,
            self::FAILED => $status === self::RETRYING,
        };
    }

    public function isFinal(): bool
    {
        return $this === self::COMPLETED || $this === self::FAILED;
    }

    public function isProcessing(): bool
    {
        return $this === self::PROCESSING
            || $this === self::VALIDATING
            || $this === self::RETRYING;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает генерации',
            self::PROCESSING => 'Генерация текстур',
            self::VALIDATING => 'Валидация качества',
            self::RETRYING => 'Повторная попытка',
            self::COMPLETED => 'Успешно завершено',
            self::FAILED => 'Ошибка генерации',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PROCESSING => 'blue',
            self::VALIDATING => 'yellow',
            self::RETRYING => 'orange',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::PROCESSING => 'heroicon-o-cpu-chip',
            self::VALIDATING => 'heroicon-o-check-circle',
            self::RETRYING => 'heroicon-o-arrow-path',
            self::COMPLETED => 'heroicon-o-check',
            self::FAILED => 'heroicon-o-x-circle',
        };
    }

    public function allowsRetry(): bool
    {
        return $this === self::FAILED;
    }

    public function getRetryDelay(): int
    {
        return match ($this) {
            self::RETRYING => 30, // seconds
            default => 0,
        };
    }

    public function getMaxRetries(): int
    {
        return 3;
    }

    /**
     * Get human-readable description for UI
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Задача поставлена в очередь на генерацию PBR-текстур',
            self::PROCESSING => 'Stable Diffusion генерирует текстуры через ControlNet',
            self::VALIDATING => 'Проверка качества сгенерированных PBR-карт',
            self::RETRYING => 'Повторная попытка генерации после ошибки',
            self::COMPLETED => 'Все PBR-текстуры успешно сгенерированы и сохранены',
            self::FAILED => 'Ошибка при генерации текстур. Проверьте логи.',
        };
    }

    /**
     * Get estimated time in seconds for current status
     */
    public function getEstimatedTime(): int
    {
        return match ($this) {
            self::PENDING => 0,
            self::PROCESSING => 240, // 4 minutes average
            self::VALIDATING => 30,
            self::RETRYING => 240,
            self::COMPLETED => 0,
            self::FAILED => 0,
        };
    }

    /**
     * Check if status allows user cancellation
     */
    public function allowsCancellation(): bool
    {
        return $this === self::PENDING || $this === self::PROCESSING;
    }

    /**
     * Get progress percentage for UI
     */
    public function getProgress(): int
    {
        return match ($this) {
            self::PENDING => 0,
            self::PROCESSING => 50,
            self::VALIDATING => 80,
            self::RETRYING => 30,
            self::COMPLETED => 100,
            self::FAILED => 0,
        };
    }
}
