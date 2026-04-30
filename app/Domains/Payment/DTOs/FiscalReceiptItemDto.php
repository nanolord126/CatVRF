<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

/**
 * DTO для товара в фискальном чеке (54-ФЗ).
 */
final readonly class FiscalReceiptItemDto
{
    /**
     * @param  string  $name  название товара
     * @param  int  $price  цена в копейках
     * @param  float  $quantity  количество
     * @param  int  $amount  сумма (price * quantity) в копейках
     * @param  string  $vat  НДС (none, vat0, vat10, vat18, vat110, vat118, vat20, vat120)
     * @param  int  $paymentType  тип оплаты (1=full_prepayment, 2=partial_prepayment, 3=advance, 4=full_payment, 5=partial_payment, 6=credit, 7=credit_payment)
     * @param  int  $paymentAgentType  тип платежного агента (0=none, 1=bank, 2=other)
     */
    public function __construct(
        public string $name,
        public int $price,
        public float $quantity,
        public int $amount,
        public string $vat = 'none',
        public int $paymentType = 4,
        public int $paymentAgentType = 0,
    ) {}

    /**
     * Преобразовать в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'vat' => $this->vat,
            'payment_type' => $this->paymentType,
            'payment_agent_type' => $this->paymentAgentType,
        ];
    }
}
