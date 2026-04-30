<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Fiscal Receipt Entity for 54-ФЗ compliance.
 * 
 * Stores KKT (cash register) receipt data for fiscalization.
 * Supports prepayment, full payment, and refund receipts.
 */
final readonly class FiscalReceipt
{
    public const TYPE_PREPAYMENT = 'prepayment';
    public const TYPE_FULL_PAYMENT = 'full_payment';
    public const TYPE_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';

    private function __construct(
        public string $uuid,
        public string $paymentIntentUuid,
        public ?int $tenantId,
        public ?int $orderId,
        public string $type, // prepayment, full_payment, refund
        public string $inn, // Seller INN
        public string $agentType, // payment_agent, bank_agent, etc.
        public string $agentName, // Platform/agent name
        public int $amountKopecks,
        public string $currency,
        public array $items, // Receipt items with VAT
        public ?string $ofdProvider, // OrangeData, CloudKassir, Atol, etc.
        public ?string $ofdReceiptId,
        public ?string $fiscalSign, // Фискальный признак
        public ?string $fiscalDocumentNumber,
        public ?string $fnNumber, // Номер ФН
        public ?CarbonImmutable $sentAt,
        public ?CarbonImmutable $confirmedAt,
        public string $status,
        public ?string $errorMessage,
        public int $retryCount,
        public CarbonImmutable $createdAt,
    ) {}

    public static function createPrepayment(
        string $paymentIntentUuid,
        ?int $tenantId,
        ?int $orderId,
        string $inn,
        string $agentName,
        int $amountKopecks,
        array $items,
    ): self {
        return new self(
            uuid: Str::uuid()->toString(),
            paymentIntentUuid: $paymentIntentUuid,
            tenantId: $tenantId,
            orderId: $orderId,
            type: self::TYPE_PREPAYMENT,
            inn: $inn,
            agentType: 'payment_agent',
            agentName: $agentName,
            amountKopecks: $amountKopecks,
            currency: 'RUB',
            items: $items,
            ofdProvider: null,
            ofdReceiptId: null,
            fiscalSign: null,
            fiscalDocumentNumber: null,
            fnNumber: null,
            sentAt: null,
            confirmedAt: null,
            status: self::STATUS_PENDING,
            errorMessage: null,
            retryCount: 0,
            createdAt: CarbonImmutable::now(),
        );
    }

    public static function createFullPayment(
        string $paymentIntentUuid,
        ?int $tenantId,
        ?int $orderId,
        string $inn,
        string $agentName,
        int $amountKopecks,
        array $items,
        ?string $prepaymentReceiptUuid = null,
    ): self {
        $receipt = new self(
            uuid: Str::uuid()->toString(),
            paymentIntentUuid: $paymentIntentUuid,
            tenantId: $tenantId,
            orderId: $orderId,
            type: self::TYPE_FULL_PAYMENT,
            inn: $inn,
            agentType: 'payment_agent',
            agentName: $agentName,
            amountKopecks: $amountKopecks,
            currency: 'RUB',
            items: $items,
            ofdProvider: null,
            ofdReceiptId: null,
            fiscalSign: null,
            fiscalDocumentNumber: null,
            fnNumber: null,
            sentAt: null,
            confirmedAt: null,
            status: self::STATUS_PENDING,
            errorMessage: null,
            retryCount: 0,
            createdAt: CarbonImmutable::now(),
        );

        return $receipt;
    }

    public static function createRefund(
        string $paymentIntentUuid,
        ?int $tenantId,
        ?int $orderId,
        string $inn,
        string $agentName,
        int $amountKopecks,
        array $items,
    ): self {
        return new self(
            uuid: Str::uuid()->toString(),
            paymentIntentUuid: $paymentIntentUuid,
            tenantId: $tenantId,
            orderId: $orderId,
            type: self::TYPE_REFUND,
            inn: $inn,
            agentType: 'payment_agent',
            agentName: $agentName,
            amountKopecks: $amountKopecks,
            currency: 'RUB',
            items: $items,
            ofdProvider: null,
            ofdReceiptId: null,
            fiscalSign: null,
            fiscalDocumentNumber: null,
            fnNumber: null,
            sentAt: null,
            confirmedAt: null,
            status: self::STATUS_PENDING,
            errorMessage: null,
            retryCount: 0,
            createdAt: CarbonImmutable::now(),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            paymentIntentUuid: $data['payment_intent_uuid'],
            tenantId: $data['tenant_id'] ? (int) $data['tenant_id'] : null,
            orderId: $data['order_id'] ? (int) $data['order_id'] : null,
            type: $data['type'],
            inn: $data['inn'],
            agentType: $data['agent_type'],
            agentName: $data['agent_name'],
            amountKopecks: (int) $data['amount_kopecks'],
            currency: $data['currency'],
            items: $data['items'],
            ofdProvider: $data['ofd_provider'] ?? null,
            ofdReceiptId: $data['ofd_receipt_id'] ?? null,
            fiscalSign: $data['fiscal_sign'] ?? null,
            fiscalDocumentNumber: $data['fiscal_document_number'] ?? null,
            fnNumber: $data['fn_number'] ?? null,
            sentAt: $data['sent_at'] ? CarbonImmutable::parse($data['sent_at']) : null,
            confirmedAt: $data['confirmed_at'] ? CarbonImmutable::parse($data['confirmed_at']) : null,
            status: $data['status'],
            errorMessage: $data['error_message'] ?? null,
            retryCount: (int) $data['retry_count'],
            createdAt: CarbonImmutable::parse($data['created_at']),
        );
    }

    public function markAsSent(string $ofdProvider, string $ofdReceiptId): self
    {
        return new self(
            uuid: $this->uuid,
            paymentIntentUuid: $this->paymentIntentUuid,
            tenantId: $this->tenantId,
            orderId: $this->orderId,
            type: $this->type,
            inn: $this->inn,
            agentType: $this->agentType,
            agentName: $this->agentName,
            amountKopecks: $this->amountKopecks,
            currency: $this->currency,
            items: $this->items,
            ofdProvider: $ofdProvider,
            ofdReceiptId: $ofdReceiptId,
            fiscalSign: $this->fiscalSign,
            fiscalDocumentNumber: $this->fiscalDocumentNumber,
            fnNumber: $this->fnNumber,
            sentAt: CarbonImmutable::now(),
            confirmedAt: $this->confirmedAt,
            status: self::STATUS_SENT,
            errorMessage: null,
            retryCount: $this->retryCount,
            createdAt: $this->createdAt,
        );
    }

    public function markAsConfirmed(
        string $fiscalSign,
        string $fiscalDocumentNumber,
        string $fnNumber,
    ): self {
        return new self(
            uuid: $this->uuid,
            paymentIntentUuid: $this->paymentIntentUuid,
            tenantId: $this->tenantId,
            orderId: $this->orderId,
            type: $this->type,
            inn: $this->inn,
            agentType: $this->agentType,
            agentName: $this->agentName,
            amountKopecks: $this->amountKopecks,
            currency: $this->currency,
            items: $this->items,
            ofdProvider: $this->ofdProvider,
            ofdReceiptId: $this->ofdReceiptId,
            fiscalSign: $fiscalSign,
            fiscalDocumentNumber: $fiscalDocumentNumber,
            fnNumber: $fnNumber,
            sentAt: $this->sentAt,
            confirmedAt: CarbonImmutable::now(),
            status: self::STATUS_CONFIRMED,
            errorMessage: null,
            retryCount: $this->retryCount,
            createdAt: $this->createdAt,
        );
    }

    public function markAsFailed(string $errorMessage): self
    {
        return new self(
            uuid: $this->uuid,
            paymentIntentUuid: $this->paymentIntentUuid,
            tenantId: $this->tenantId,
            orderId: $this->orderId,
            type: $this->type,
            inn: $this->inn,
            agentType: $this->agentType,
            agentName: $this->agentName,
            amountKopecks: $this->amountKopecks,
            currency: $this->currency,
            items: $this->items,
            ofdProvider: $this->ofdProvider,
            ofdReceiptId: $this->ofdReceiptId,
            fiscalSign: $this->fiscalSign,
            fiscalDocumentNumber: $this->fiscalDocumentNumber,
            fnNumber: $this->fnNumber,
            sentAt: $this->sentAt,
            confirmedAt: $this->confirmedAt,
            status: self::STATUS_FAILED,
            errorMessage: $errorMessage,
            retryCount: $this->retryCount + 1,
            createdAt: $this->createdAt,
        );
    }

    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->retryCount < 3;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'payment_intent_uuid' => $this->paymentIntentUuid,
            'tenant_id' => $this->tenantId,
            'order_id' => $this->orderId,
            'type' => $this->type,
            'inn' => $this->inn,
            'agent_type' => $this->agentType,
            'agent_name' => $this->agentName,
            'amount_kopecks' => $this->amountKopecks,
            'currency' => $this->currency,
            'items' => $this->items,
            'ofd_provider' => $this->ofdProvider,
            'ofd_receipt_id' => $this->ofdReceiptId,
            'fiscal_sign' => $this->fiscalSign,
            'fiscal_document_number' => $this->fiscalDocumentNumber,
            'fn_number' => $this->fnNumber,
            'sent_at' => $this->sentAt?->toDateTimeString(),
            'confirmed_at' => $this->confirmedAt?->toDateTimeString(),
            'status' => $this->status,
            'error_message' => $this->errorMessage,
            'retry_count' => $this->retryCount,
            'created_at' => $this->createdAt->toDateTimeString(),
        ];
    }
}
