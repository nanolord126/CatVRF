<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Entities;

use Modules\Wallet\Domain\Events\WalletDeposited;
use Modules\Wallet\Domain\Events\WalletTransferred;
use Modules\Wallet\Domain\Events\WalletWithdrawn;
use Modules\Wallet\Domain\Exceptions\InsufficientFundsException;
use Modules\Wallet\Domain\ValueObjects\Money;

/**
 * Агрегат Кошелёк (Wallet Aggregate Root).
 *
 * Инвариант: баланс ≥ holdAmount ≥ 0.
 * Все суммы — в копейках (int).
 */
final class WalletAggregate
{
    /** @var list<object> Доменные события, выпущенные агрегатом */
    private array $domainEvents = [];

    /**
     * Транзакции, которые нужно записать в БД при сохранении.
     * @var list<array{type:string, amount:int, description:string, correlation_id:string}>
     */
    private array $pendingTransactions = [];

    private function __construct(
        private readonly int   $id,
        private readonly int   $tenantId,
        private readonly int   $userId,
        private Money          $balance,
        private Money          $holdAmount,
    ) {}

    // ──────────────────────────────────────────────
    //  Factories
    // ──────────────────────────────────────────────

    public static function create(
        int $id,
        int $tenantId,
        int $userId,
    ): self {
        return new self(
            id:         $id,
            tenantId:   $tenantId,
            userId:     $userId,
            balance:    Money::ofKopeks(0),
            holdAmount: Money::ofKopeks(0),
        );
    }

    public static function reconstitute(
        int   $id,
        int   $tenantId,
        int   $userId,
        Money $balance,
        Money $holdAmount,
    ): self {
        return new self(
            id:         $id,
            tenantId:   $tenantId,
            userId:     $userId,
            balance:    $balance,
            holdAmount: $holdAmount,
        );
    }

    // ──────────────────────────────────────────────
    //  Commands
    // ──────────────────────────────────────────────

    /** Пополнить баланс */
    public function deposit(Money $amount, string $description, string $correlationId): void
    {
        $this->balance = $this->balance->add($amount);

        $this->pendingTransactions[] = [
            'type'           => 'deposit',
            'amount'         => $amount->toKopeks(),
            'description'    => $description,
            'correlation_id' => $correlationId,
        ];

        $this->domainEvents[] = new WalletDeposited(
            walletId:      $this->id,
            tenantId:      $this->tenantId,
            userId:        $this->userId,
            amount:        $amount,
            newBalance:    $this->balance,
            description:   $description,
            correlationId: $correlationId,
        );
    }

    /** Снять с баланса */
    public function withdraw(Money $amount, string $description, string $correlationId): void
    {
        $available = $this->getAvailableBalance();

        if ($amount->isGreaterThan($available)) {
            throw InsufficientFundsException::forWithdraw(
                $amount->toKopeks(),
                $available->toKopeks(),
            );
        }

        $this->balance = $this->balance->subtract($amount);

        $this->pendingTransactions[] = [
            'type'           => 'withdraw',
            'amount'         => $amount->toKopeks(),
            'description'    => $description,
            'correlation_id' => $correlationId,
        ];

        $this->domainEvents[] = new WalletWithdrawn(
            walletId:      $this->id,
            tenantId:      $this->tenantId,
            userId:        $this->userId,
            amount:        $amount,
            newBalance:    $this->balance,
            description:   $description,
            correlationId: $correlationId,
        );
    }

    /**
     * Перевести на другой кошелёк.
     * Withdraw из self + deposit в recipient + событие Transfer.
     */
    public function transferTo(
        WalletAggregate $recipient,
        Money           $amount,
        string          $description,
        string          $correlationId,
    ): void {
        $this->withdraw($amount, "Перевод: {$description}", $correlationId);
        $recipient->deposit($amount, "Перевод: {$description}", $correlationId);

        $this->domainEvents[] = new WalletTransferred(
            fromWalletId:  $this->id,
            toWalletId:    $recipient->getId(),
            tenantId:      $this->tenantId,
            amount:        $amount,
            correlationId: $correlationId,
        );
    }

    /** Поставить на холд (резервировать средства) */
    public function hold(Money $amount): void
    {
        $available = $this->getAvailableBalance();

        if ($amount->isGreaterThan($available)) {
            throw InsufficientFundsException::forHold(
                $amount->toKopeks(),
                $available->toKopeks(),
            );
        }

        $this->holdAmount = $this->holdAmount->add($amount);
    }

    /** Снять холд */
    public function releaseHold(Money $amount): void
    {
        $release = $amount->isGreaterThan($this->holdAmount)
            ? $this->holdAmount
            : $amount;

        $this->holdAmount = $this->holdAmount->subtract($release);
    }

    // ──────────────────────────────────────────────
    //  Getters
    // ──────────────────────────────────────────────

    public function getId(): int           { return $this->id; }
    public function getTenantId(): int     { return $this->tenantId; }
    public function getUserId(): int       { return $this->userId; }
    public function getBalance(): Money    { return $this->balance; }
    public function getHoldAmount(): Money { return $this->holdAmount; }

    public function getAvailableBalance(): Money
    {
        return Money::ofKopeks(
            max(0, $this->balance->toKopeks() - $this->holdAmount->toKopeks())
        );
    }

    // ──────────────────────────────────────────────
    //  Event / Transaction collectors
    // ──────────────────────────────────────────────

    /** Вернуть и очистить доменные события */
    public function pullDomainEvents(): array
    {
        $events             = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * Вернуть и очистить список транзакций для записи в БД.
     * @return list<array{type:string, amount:int, description:string, correlation_id:string}>
     */
    public function popPendingTransactions(): array
    {
        $txs                       = $this->pendingTransactions;
        $this->pendingTransactions = [];

        return $txs;
    }
}
