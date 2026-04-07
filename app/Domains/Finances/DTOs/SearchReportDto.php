<?php

declare(strict_types=1);

namespace App\Domains\Finances\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для поиска и фильтрации финансовых отчётов.
 *
 * Immutable value object. Содержит критерии поиска
 * по типу отчёта, формату, статусу и периоду.
 *
 * @package App\Domains\Finances\DTOs
 */
final readonly class SearchReportDto
{
    /**
     * @param int         $tenantId        Идентификатор тенанта
     * @param int|null    $businessGroupId Фильтр по бизнес-группе (B2B)
     * @param int         $userId          Текущий пользователь
     * @param string      $correlationId   Сквозной correlation ID
     * @param string|null $query           Текстовый поиск
     * @param string|null $status          Фильтр по статусу (pending, completed, failed)
     * @param string|null $reportType      Фильтр по типу отчёта
     * @param string|null $format          Фильтр по формату (json, csv, xlsx, pdf)
     * @param string|null $periodStart     Начало периода (дата создания отчёта)
     * @param string|null $periodEnd       Конец периода
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
        public ?string $reportType = null,
        public ?string $format = null,
        public ?string $periodStart = null,
        public ?string $periodEnd = null,
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
            reportType:      $request->input('report_type'),
            format:          $request->input('format'),
            periodStart:     $request->input('period_start'),
            periodEnd:       $request->input('period_end'),
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
        return ['created_at', 'updated_at', 'report_type', 'format', 'status'];
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
            || $this->status !== null
            || $this->reportType !== null
            || $this->format !== null
            || $this->periodStart !== null;
    }
}
