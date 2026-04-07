<?php

declare(strict_types=1);

namespace App\Domains\Finances\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для поиска и фильтрации бюджетов.
 *
 * Immutable value object. Содержит критерии поиска,
 * сортировку и пагинацию. Тенант-aware и B2B-aware.
 *
 * @package App\Domains\Finances\DTOs
 */
final readonly class SearchBudgetDto
{
    /**
     * @param int         $tenantId        Идентификатор тенанта
     * @param int|null    $businessGroupId Фильтр по бизнес-группе (B2B)
     * @param int         $userId          Текущий пользователь
     * @param string      $correlationId   Сквозной correlation ID
     * @param string|null $query           Текстовый поиск по названию
     * @param string|null $status          Фильтр по статусу (active, exhausted, archived)
     * @param string|null $currency        Фильтр по валюте
     * @param int|null    $minAmount       Минимальная сумма (копейки)
     * @param int|null    $maxAmount       Максимальная сумма (копейки)
     * @param string      $sortBy          Поле сортировки
     * @param string      $sortDir         Направление сортировки
     * @param int         $perPage         Количество на странице (max 100)
     * @param int         $page            Номер страницы
     * @param bool        $isB2B           Флаг B2B-контекста
     */
    public function __construct(
        public int     $tenantId,
        public ?int    $businessGroupId,
        public int     $userId,
        public string  $correlationId,
        public ?string $query = null,
        public ?string $status = null,
        public ?string $currency = null,
        public ?int    $minAmount = null,
        public ?int    $maxAmount = null,
        public string  $sortBy = 'created_at',
        public string  $sortDir = 'desc',
        public int     $perPage = 20,
        public int     $page = 1,
        public bool    $isB2B = false,
    ) {}

    /**
     * Создать DTO из HTTP-запроса.
     */
    public static function from(Request $request): self
    {
        return new self(
            tenantId:        (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id')
                ? (int) $request->input('business_group_id')
                : null,
            userId:          (int) $request->user()?->id,
            correlationId:   (string) $request->header(
                'X-Correlation-ID',
                Str::uuid()->toString(),
            ),
            query:           $request->input('q'),
            status:          $request->input('status'),
            currency:        $request->input('currency'),
            minAmount:       $request->filled('min_amount')
                ? (int) $request->input('min_amount')
                : null,
            maxAmount:       $request->filled('max_amount')
                ? (int) $request->input('max_amount')
                : null,
            sortBy:          (string) $request->input('sort_by', 'created_at'),
            sortDir:         (string) $request->input('sort_dir', 'desc'),
            perPage:         min((int) $request->input('per_page', 20), 100),
            page:            max((int) $request->input('page', 1), 1),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Получить допустимые поля сортировки.
     *
     * @return array<int, string>
     */
    public static function allowedSortFields(): array
    {
        return ['created_at', 'updated_at', 'amount', 'name', 'period_start', 'period_end'];
    }

    /**
     * Проверить, допустимо ли поле сортировки.
     */
    public function getSanitizedSortBy(): string
    {
        return in_array($this->sortBy, self::allowedSortFields(), true)
            ? $this->sortBy
            : 'created_at';
    }

    /**
     * Получить безопасное направление сортировки.
     */
    public function getSanitizedSortDir(): string
    {
        return in_array($this->sortDir, ['asc', 'desc'], true)
            ? $this->sortDir
            : 'desc';
    }

    /**
     * Есть ли активные фильтры.
     */
    public function hasFilters(): bool
    {
        return $this->query !== null
            || $this->status !== null
            || $this->currency !== null
            || $this->minAmount !== null
            || $this->maxAmount !== null;
    }
}
