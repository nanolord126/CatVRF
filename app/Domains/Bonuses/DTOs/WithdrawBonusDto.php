<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

use App\Domains\Bonuses\Enums\BonusTier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для вывода бонусов в реальные деньги.
 *
 * CANON 2026: final readonly, public properties, from(Request), toArray(), toAuditContext().
 * Только для B2B Gold/Platinum пользователей.
 */
final readonly class WithdrawBonusDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $amount,
        public BonusTier $userTier,
        public ?string $bankAccount = null,
        public ?string $withdrawalMethod = null,
        public ?string $reason = null,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {}

    /** Создание из HTTP-запроса. */
    public static function from(Request $request): self
    {
        return new self(
            userId: (int) $request->input('user_id'),
            tenantId: (int) $request->input('tenant_id'),
            amount: (int) $request->input('amount'),
            userTier: BonusTier::from($request->input('user_tier', 'bronze')),
            bankAccount: $request->input('bank_account'),
            withdrawalMethod: $request->input('withdrawal_method'),
            reason: $request->input('reason'),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            idempotencyKey: $request->input('idempotency_key'),
            metadata: $request->input('metadata', []),
        );
    }

    /** Создание из массива (для Jobs и внутренних вызовов). */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            tenantId: (int) $data['tenant_id'],
            amount: (int) $data['amount'],
            userTier: BonusTier::from($data['user_tier'] ?? 'bronze'),
            bankAccount: $data['bank_account'] ?? null,
            withdrawalMethod: $data['withdrawal_method'] ?? null,
            reason: $data['reason'] ?? null,
            correlationId: $data['correlation_id'] ?? Str::uuid()->toString(),
            idempotencyKey: $data['idempotency_key'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /** Преобразование в массив для хранения. */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount,
            'user_tier' => $this->userTier->value,
            'bank_account' => $this->bankAccount,
            'withdrawal_method' => $this->withdrawalMethod,
            'reason' => $this->reason,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'metadata' => $this->metadata,
            'commission_amount' => $this->calculateCommission(),
            'payout_amount' => $this->getPayoutAmount(),
        ];
    }

    /** Контекст для аудит-лога. */
    public function toAuditContext(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount,
            'user_tier' => $this->userTier->value,
            'withdrawal_method' => $this->withdrawalMethod,
            'correlation_id' => $this->correlationId,
            'commission_amount' => $this->calculateCommission(),
            'payout_amount' => $this->getPayoutAmount(),
        ];
    }

    /** Проверить, разрешён ли вывод для данного tier. */
    public function canWithdraw(): bool
    {
        return $this->userTier->canWithdrawToRealMoney();
    }

    /** Проверить валидность DTO. */
    public function validate(): bool
    {
        return $this->amount > 0
            && $this->userId > 0
            && $this->tenantId > 0
            && $this->canWithdraw();
    }

    /** Рассчитать комиссию за вывод. */
    public function calculateCommission(): int
    {
        return (int) ($this->amount * ($this->userTier->getWithdrawalCommission() / 100));
    }

    /** Получить сумму к выплате (за вычетом комиссии). */
    public function getPayoutAmount(): int
    {
        return $this->amount - $this->calculateCommission();
    }
}
