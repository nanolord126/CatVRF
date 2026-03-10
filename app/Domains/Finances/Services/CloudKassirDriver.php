<?php

namespace App\Domains\Finances\Services;

use App\Domains\Finances\Interfaces\FiscalServiceInterface;
use Illuminate\Support\Facades\Http;

class CloudKassirDriver implements FiscalServiceInterface
{
    private array $conf;
    public function __construct() { $this->conf = config('payments.fiscal.cloud_kassir'); }

    public function sendReceipt(array $tx): array {
        $res = Http::withBasicAuth($this->conf['id'], $this->conf['key'])
            ->post('https://api.cloudpayments.ru/kassa/receipt', [
                'Inn' => $this->conf['inn'], 'Type' => 'Income',
                'CustomerReceipt' => $this->buildReceipt($tx),
                'InvoiceId' => $tx['order_id'], 'AccountId' => $tx['user_id'],
            ])->json();
        return ['fiscal_id' => $res['Model']['Id'] ?? null, 'url' => $res['Model']['Url'] ?? ''];
    }

    public function getReceiptStatus(string $id): array { return []; }
    public function refundReceipt(string $id, array $d): bool { return true; }

    private function buildReceipt($tx): array {
        return ['Items' => array_map(fn($i) => [
            'label' => $i['name'], 'price' => $i['price'], 'quantity' => $i['qty'],
            'amount' => $i['price'] * $i['qty'], 'vat' => $i['vat'] ?? 'None'
        ], $tx['items']), 'taxationSystem' => $this->conf['tax_system']];
    }
}
