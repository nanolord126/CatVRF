<?php

declare(strict_types=1);

namespace App\Domains\Finances\DTOs;

use Illuminate\Support\Str;

/**
 * DTO для расчёта комиссии платформы.
 *
 * CatVRF Canon 2026 — Layer 2 (DTOs).
 * Иммутабельный объект — все свойства readonly.
 * Поддерживает конвертацию из массива и HTTP-запроса.
 *
 * @package App\Domains\Finances\DTOs
 */
final readonly class CalculateCommissionDto
{
    /**
     * @param int    $amountKopecks Сумма операции в копейках (> 0)
     * @param bool   $isB2B         Признак юридического лица
     * @param string $b2bTier       Уровень B2B: standard|silver|gold|platinum
     * @param string $vertical      Название вертикали (beauty, food и т.д.)
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function __construct(
        public int $amountKopecks,
        public bool $isB2B,
        public string $b2bTier,
        public string $vertical,
        public string $correlationId,
    ) {}

    /**
     * Создать DTO из ассоциативного массива.
     *
     * @param array{amount_kopecks: int, is_b2b: bool, b2b_tier?: string, vertical: string, correlation_id?: string} $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amountKopecks: (int) ($data['amount_kopecks'] ?? 0),
            isB2B: (bool) ($data['is_b2b'] ?? false),
            b2bTier: (string) ($data['b2b_tier'] ?? 'standard'),
            vertical: (string) ($data['vertical'] ?? ''),
            correlationId: (string) ($data['correlation_id'] ?? Str::uuid()->toString()),
        );
    }

    /**
     * Создать DTO из HTTP-запроса.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return self
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            amountKopecks: (int) $request->input('amount_kopecks', 0),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
            b2bTier: (string) $request->input('b2b_tier', 'standard'),
            vertical: (string) $request->input('vertical', ''),
            correlationId: (string) ($request->header('X-Correlation-ID') ?? Str::uuid()->toString()),
        );
    }

    /**
     * Конвертировать в массив для логирования / сериализации.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount_kopecks' => $this->amountKopecks,
            'is_b2b'         => $this->isB2B,
            'b2b_tier'       => $this->b2bTier,
            'vertical'       => $this->vertical,
            'correlation_id' => $this->correlationId,
        ];
    }
}
