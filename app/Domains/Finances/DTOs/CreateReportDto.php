<?php

declare(strict_types=1);

namespace App\Domains\Finances\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для создания финансового отчёта.
 *
 * Immutable value object. Определяет тип отчёта, период,
 * формат и метаданные. Поддерживает B2C/B2B через флаг.
 *
 * @package App\Domains\Finances\DTOs
 */
final readonly class CreateReportDto
{
    /**
     * @param int         $tenantId        Идентификатор тенанта
     * @param int|null    $businessGroupId Идентификатор бизнес-группы (B2B)
     * @param int         $userId          Идентификатор запросившего пользователя
     * @param string      $correlationId   Сквозной correlation ID
     * @param string      $reportType      Тип отчёта (revenue, expenses, payout, tax, custom)
     * @param string      $periodStart     Начало периода (Y-m-d)
     * @param string      $periodEnd       Конец периода (Y-m-d)
     * @param string      $format          Формат выгрузки (json, csv, xlsx, pdf)
     * @param array       $filters         Дополнительные фильтры
     * @param array       $metadata        Метаданные отчёта
     * @param string|null $idempotencyKey  Ключ идемпотентности
     * @param bool        $isB2B           Флаг B2B-операции
     */
    public function __construct(
        public int     $tenantId,
        public ?int    $businessGroupId,
        public int     $userId,
        public string  $correlationId,
        public string  $reportType,
        public string  $periodStart,
        public string  $periodEnd,
        public string  $format = 'json',
        public array   $filters = [],
        public array   $metadata = [],
        public ?string $idempotencyKey = null,
        public bool    $isB2B = false,
    ) {}

    /**
     * Создать DTO из HTTP-запроса.
     */
    public static function from(Request $request): self
    {
        $validated = $request->validated();

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
            reportType:      (string) ($validated['report_type'] ?? 'revenue'),
            periodStart:     (string) ($validated['period_start'] ?? ''),
            periodEnd:       (string) ($validated['period_end'] ?? ''),
            format:          (string) ($validated['format'] ?? 'json'),
            filters:         (array) ($validated['filters'] ?? []),
            metadata:        (array) ($validated['metadata'] ?? []),
            idempotencyKey:  $request->header('Idempotency-Key'),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Преобразовать в массив для сохранения.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id'           => $this->userId,
            'correlation_id'    => $this->correlationId,
            'report_type'       => $this->reportType,
            'period_start'      => $this->periodStart,
            'period_end'        => $this->periodEnd,
            'format'            => $this->format,
            'filters'           => $this->filters,
            'metadata'          => $this->metadata,
        ];
    }

    /**
     * Контекст для аудит-лога.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id'           => $this->userId,
            'correlation_id'    => $this->correlationId,
            'report_type'       => $this->reportType,
            'period'            => $this->periodStart . ' — ' . $this->periodEnd,
            'format'            => $this->format,
            'is_b2b'            => $this->isB2B,
        ];
    }

    /**
     * Проверить, является ли запрошенный формат бинарным (pdf/xlsx).
     */
    public function isBinaryFormat(): bool
    {
        return in_array($this->format, ['pdf', 'xlsx'], true);
    }
}
