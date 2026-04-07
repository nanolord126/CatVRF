<?php

declare(strict_types=1);

namespace App\Domains\Finances\DTOs;

use App\Domains\Finances\Domain\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для поиска и фильтрации транзакций.
 *
 * Immutable value object. Содержит критерии поиска
 * по типу транзакции, wallet_id, сумме и периоду.
 *
 * @package App\Domains\Finances\DTOs
 */
final readonly class SearchTransactionDto
{
    /**
     * @param int                  $tenantId        Идентификатор тенанта
     * @param int|null             $businessGroupId Фильтр по бизнес-группе (B2B)
     * @param int                  $userId          Текущий пользователь
     * @param string               $correlationId   Сквозной correlation ID
     * @param string|null          $query           Текстовый поиск по описанию
     * @param TransactionType|null $type            Фильтр по типу транзакции
     * @param int|null             $walletId        Фильтр по кошельку
     * @param int|null             $minAmount       Минимальная сумма (копейки)
     * @param int|null             $maxAmount       Максимальная сумма (копейки)
     * @param string|null          $dateFrom        Начало периода (Y-m-d)
     * @param string|null          $dateTo          Конец периода (Y-m-d)
     * @param string               $sortBy          Поле сортировки
     * @param string               $sortDir         Направление
     * @param int                  $perPage         Количество на странице (max 100)
     * @param int                  $page            Номер страницы
     * @param bool                 $isB2B           Флаг B2B-контекста
     */
    public function __construct(
        public int              $tenantId,
        public ?int             $businessGroupId,
        public int              $userId,
        public string           $correlationId,
        public ?string          $query = null,
        public ?TransactionType $type = null,
        public ?int             $walletId = null,
        public ?int             $minAmount = null,
        public ?int             $maxAmount = null,
        public ?string          $dateFrom = null,
        public ?string          $dateTo = null,
        public string           $sortBy = 'created_at',
        public string           $sortDir = 'desc',
        public int              $perPage = 20,
        public int              $page = 1,
        public bool             $isB2B = false,
    ) {}

    /**
     * Создать DTO из HTTP-запроса.
     */
    public static function from(Request $request): self
    {
        $typeValue = $request->input('type');

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
            type:            $typeValue !== null
                ? TransactionType::tryFrom((string) $typeValue)
                : null,
            walletId:        $request->filled('wallet_id')
                ? (int) $request->input('wallet_id')
                : null,
            minAmount:       $request->filled('min_amount')
                ? (int) $request->input('min_amount')
                : null,
            maxAmount:       $request->filled('max_amount')
                ? (int) $request->input('max_amount')
                : null,
            dateFrom:        $request->input('date_from'),
            dateTo:          $request->input('date_to'),
            sortBy:          (string) $request->input('sort_by', 'created_at'),
            sortDir:         (string) $request->input('sort_dir', 'desc'),
            perPage:         min((int) $request->input('per_page', 20), 100),
            page:            max((int) $request->input('page', 1), 1),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Допустимые поля сортировки.
     *
     * @return array<int, string>
     */
    public static function allowedSortFields(): array
    {
        return ['created_at', 'updated_at', 'amount', 'type', 'wallet_id'];
    }

    /**
     * Получить безопасное поле сортировки.
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
            || $this->type !== null
            || $this->walletId !== null
            || $this->minAmount !== null
            || $this->maxAmount !== null
            || $this->dateFrom !== null;
    }
}
