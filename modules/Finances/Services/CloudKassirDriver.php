<?php

namespace App\Domains\Finances\

/**
 * CloudKassirDriver
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CloudKassirDriver();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Finances\Services
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
Services;

use App\Domains\Finances\Interfaces\FiscalServiceInterface;
use Illuminate\Support\Facades\Http;

class CloudKassirDriver implements FiscalServiceInterface
{
    private array $conf;
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function __construct() { $this->conf = config('payments.fiscal.cloud_kassir'); }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function sendReceipt(array $tx): array {
        $res = Http::withBasicAuth($this->conf['id'], $this->conf['key'])
            ->post('https://api.cloudpayments.ru/kassa/receipt', [
                'Inn' => $this->conf['inn'], 'Type' => 'Income',
                'CustomerReceipt' => $this->buildReceipt($tx),
                'InvoiceId' => $tx['order_id'], 'AccountId' => $tx['user_id'],
            ])->json();
        return ['fiscal_id' => $res['Model']['Id'] ?? null, 'url' => $res['Model']['Url'] ?? ''];
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function getReceiptStatus(string $id): array { return []; }
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function refundReceipt(string $id, array $d): bool { return true; }

    private function buildReceipt($tx): array {
        return ['Items' => array_map(fn($i) => [
            'label' => $i['name'], 'price' => $i['price'], 'quantity' => $i['qty'],
            'amount' => $i['price'] * $i['qty'], 'vat' => $i['vat'] ?? 'None'
        ], $tx['items']), 'taxationSystem' => $this->conf['tax_system']];
    }
}
