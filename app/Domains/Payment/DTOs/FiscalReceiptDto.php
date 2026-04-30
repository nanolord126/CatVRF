<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

/**
 * DTO для фискального чека (54-ФЗ).
 *
 * Содержит все необходимые данные для отправки чека в ОФД.
 */
final readonly class FiscalReceiptDto
{
    /**
     * @param  string  $type  тип чека (sell, sell_refund, buy, buy_refund)
     * @param  string  $taxationType  система налогообложения (osn, usn_income, usn_income_outcome, patent, envd, esn)
     * @param  int  $totalAmount  общая сумма в копейках
     * @param  array<int, FiscalReceiptItemDto>  $items  товары в чеке
     * @param  int  $paymentType  тип оплаты (1=cash, 2=electronically, 3=advance, 4=credit, 5=compensation)
     * @param  string|null  $customerEmail  email клиента для отправки чека
     * @param  string|null  $customerPhone  телефон клиента для отправки чека
     * @param  array<string, mixed>|null  $metadata  дополнительные метаданные
     */
    public function __construct(
        public string $type,
        public string $taxationType,
        public int $totalAmount,
        public array $items,
        public int $paymentType = 2, // по умолчанию электронная оплата
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Преобразовать в массив для отправки в ОФД.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'taxation_type' => $this->taxationType,
            'total_amount' => $this->totalAmount,
            'items' => array_map(fn (FiscalReceiptItemDto $item) => $item->toArray(), $this->items),
            'payment_type' => $this->paymentType,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'metadata' => $this->metadata,
        ];
    }
}
