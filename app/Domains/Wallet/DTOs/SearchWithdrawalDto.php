<?php

declare(strict_types=1);

namespace App\Domains\Wallet\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для поиска/фильтрации выводов.
 *
 * CANON 2026: final readonly, public properties, from(Request).
 */
final readonly class SearchWithdrawalDto
{
    public function __construct(
        public ?int $walletId = null,
        public ?int $tenantId = null,
        public ?int $businessGroupId = null,
        public ?int $minAmount = null,
        public ?int $maxAmount = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $correlationId = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}

    /** Создание из HTTP-запроса. */
    public static function from(Request $request): self
    {
        return new self(
            walletId: $request->filled('wallet_id') ? (int) $request->input('wallet_id') : null,
            tenantId: $request->filled('tenant_id') ? (int) $request->input('tenant_id') : null,
            businessGroupId: $request->filled('business_group_id') ? (int) $request->input('business_group_id') : null,
            minAmount: $request->filled('min_amount') ? (int) $request->input('min_amount') : null,
            maxAmount: $request->filled('max_amount') ? (int) $request->input('max_amount') : null,
            status: $request->input('status'),
            dateFrom: $request->input('date_from'),
            dateTo: $request->input('date_to'),
            correlationId: $request->header('X-Correlation-ID'),
            page: (int) $request->input('page', 1),
            perPage: min((int) $request->input('per_page', 20), 100),
        );
    }

    /** Преобразование в массив для query builder. */
    public function toArray(): array
    {
        return array_filter([
            'wallet_id' => $this->walletId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'min_amount' => $this->minAmount,
            'max_amount' => $this->maxAmount,
            'status' => $this->status,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
