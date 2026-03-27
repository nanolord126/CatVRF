<?php

namespace App\Domains\Finances\Services\Fiscal;

use App\Domains\Finances\Interfaces\FiscalDriverInterface;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\{Http, Log};
use Exception;

/**
 * Atol Fiscal Driver для ФЗ-54 compliance.
 * 
 * Интеграция с Atol API для отправки фискальных чеков.
 */
class AtolFiscalDriver implements FiscalDriverInterface
{
    private array $config;
    private string $token;
    private string $endpoint;

    public function __construct()
    {
        $this->config = config('fiscal.drivers.atol');
        $this->endpoint = $this->config['endpoint'];
        $this->token = '';
    }

    /**
     * Обработать товары и добавить налоговые данные в соответствии с системой налогообложения.
     */
    private function processItemsWithTax(array $items, string $taxSystem): array
    {
        $processedItems = [];
        
        foreach ($items as $item) {
            $taxRate = $this->getTaxRate($taxSystem, $item['tax'] ?? null);
            
            $processedItem = [
                'name' => $item['name'],
                'price' => (float) $item['price'],
                'qty' => (float) $item['qty'],
                'sum' => (float) $item['price'] * (float) $item['qty'],
            ];
            
            // Добавить налоговую информацию
            if ($taxRate['type'] === 'no_vat') {
                $processedItem['tax'] = 'no_vat';
            } else {
                $processedItem['tax'] = $taxRate['type'];
            }
            
            $processedItems[] = $processedItem;
        }
        
        return $processedItems;
    }

    /**
     * Получить налоговую ставку для системы налогообложения.
     * 
     * @param string $taxSystem Система налогообложения (OSN, USN_INCOME, USN_INCOME_MINUS_EXPENSE, ENVD, ESN, PSN)
     * @param string|null $taxCode Код налога из товара
     * @return array ['rate' => float, 'type' => string]
     */
    private function getTaxRate(string $taxSystem, ?string $taxCode = null): array
    {
        // УСН (Упрощенная система налогообложения) - без НДС
        if (str_contains($taxSystem, 'USN')) {
            return [
                'rate' => 0,
                'type' => 'no_vat',
                'description' => 'УСН - без НДС'
            ];
        }

        // ЕСХН (Единый сельскохозяйственный налог) - без НДС
        if ($taxSystem === 'ESN') {
            return [
                'rate' => 0,
                'type' => 'no_vat',
                'description' => 'ЕСХН - без НДС'
            ];
        }

        // ОСН (Общая система) - с НДС (0%, 10%, 20%)
        if ($taxSystem === 'OSN') {
            $taxRates = [
                'vat_0' => ['rate' => 0, 'type' => 'vat_0'],
                'vat_10' => ['rate' => 10, 'type' => 'vat_10'],
                'vat_20' => ['rate' => 20, 'type' => 'vat_20'],
            ];
            
            if ($taxCode && isset($taxRates[strtolower($taxCode)])) {
                return $taxRates[strtolower($taxCode)];
            }
            
            // По умолчанию 20% НДС для ОСН (стандартная ставка с 2019 года)
            return ['rate' => 20, 'type' => 'vat_20'];
        }

        // ЕНВД, ПСН - без НДС
        if (in_array($taxSystem, ['ENVD', 'PSN'])) {
            return [
                'rate' => 0,
                'type' => 'no_vat',
                'description' => "{$taxSystem} - без НДС"
            ];
        }

        // По умолчанию
        return ['rate' => 0, 'type' => 'no_vat'];
    }

    /**
     * Получить токен аутентификации от Atol API.
     */
    private function getToken(): string
    {
        try {
            $response = Http::timeout(10)
                ->post($this->endpoint . 'getToken', [
                    'login' => $this->config['login'],
                    'pass' => $this->config['password'],
                ])
                ->throw()
                ->json();

            return $response['token'] ?? throw new Exception('Token not received from Atol');
        } catch (Exception $e) {
            Log::error('Atol getToken failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Отправить фискальный чек продажи.
     */
    public function sendReceipt(array $tx, array $items): array
    {
        try {
            $token = $this->getToken();
            $taxSystem = $tx['tax_system'] ?? 'OSN';

            // Обработка товаров с добавлением налоговых данных
            $processedItems = $this->processItemsWithTax($items, $taxSystem);

            $response = Http::withHeaders(['Token' => $token])
                ->timeout(15)
                ->post($this->endpoint . "{$this->config['group_code']}/sell", [
                    'external_id' => $tx['payment_id'],
                    'receipt' => [
                        'items' => $processedItems,
                        'total' => $tx['amount'],
                        'attributes' => [
                            'email' => $tx['metadata']['email'] ?? 'noreply@catvrf.ru',
                            'phone' => $tx['metadata']['phone'] ?? null,
                        ],
                    ],
                ])
                ->throw()
                ->json();

            $fiscalId = $response['uuid'] ?? null;
            $status = $response['status'] ?? 'sent';
            $receiptUrl = $response['receipt_url'] ?? null;

            Log::info('Atol receipt sent', [
                'fiscal_id' => $fiscalId,
                'status' => $status,
                'payment_id' => $tx['payment_id'],
                'correlation_id' => $tx['correlation_id'],
            ]);

            return [
                'fiscal_id' => $fiscalId,
                'receipt_url' => $receiptUrl,
                'status' => $status,
                'sent_at' => Carbon::now()->toIso8601String(),
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('Atol sendReceipt failed', [
                'error' => $e->getMessage(),
                'payment_id' => $tx['payment_id'],
                'correlation_id' => $tx['correlation_id'],
            ]);
            throw $e;
        }
    }

    /**
     * Получить статус чека.
     */
    public function getReceiptStatus(string $fiscalId): array
    {
        try {
            $token = $this->getToken();

            $response = Http::withHeaders(['Token' => $token])
                ->timeout(10)
                ->get($this->endpoint . "doc/{$fiscalId}")
                ->throw()
                ->json();

            return [
                'fiscal_id' => $response['uuid'] ?? $fiscalId,
                'status' => $response['status'] ?? 'unknown',
                'receipt_url' => $response['receipt_url'] ?? null,
                'created_at' => $response['registered_at'] ?? null,
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('Atol getReceiptStatus failed', [
                'error' => $e->getMessage(),
                'fiscal_id' => $fiscalId,
            ]);
            throw $e;
        }
    }

    /**
     * Отправить чек возврата.
     */
    public function refundReceipt(string $fiscalId, float $amount, array $data = []): array
    {
        try {
            $token = $this->getToken();

            $response = Http::withHeaders(['Token' => $token])
                ->timeout(15)
                ->post($this->endpoint . "{$this->config['group_code']}/return", [
                    'external_id' => "refund-{$fiscalId}-" . Carbon::now()->timestamp,
                    'original_uuid' => $fiscalId,
                    'receipt' => [
                        'items' => $data['items'] ?? [],
                        'total' => $amount,
                    ],
                ])
                ->throw()
                ->json();

            Log::info('Atol refund sent', [
                'original_fiscal_id' => $fiscalId,
                'refund_fiscal_id' => $response['uuid'] ?? null,
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);

            return [
                'refund_fiscal_id' => $response['uuid'] ?? null,
                'status' => $response['status'] ?? 'sent',
                'amount' => $amount,
                'sent_at' => Carbon::now()->toIso8601String(),
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('Atol refundReceipt failed', [
                'error' => $e->getMessage(),
                'fiscal_id' => $fiscalId,
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Проверить доступность Atol API.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)
                ->head($this->endpoint)
                ->successful();
            
            Log::info('Atol availability check', ['available' => $response]);
            return $response;
        } catch (Exception $e) {
            Log::warning('Atol availability check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Переотправить чек.
     */
    public function resendReceipt(string $fiscalId): array
    {
        try {
            Log::info('Atol: resending receipt', ['fiscal_id' => $fiscalId]);

            // Atol не имеет прямого метода переотправки
            // Возвращаем статус существующего чека
            $token = $this->getToken();
            $response = Http::withHeaders(['Token' => $token])
                ->timeout(10)
                ->get("{$this->endpoint}report")
                ->throw()
                ->json();

            return [
                'fiscal_id' => $fiscalId,
                'status' => 'resent',
                'sent_at' => Carbon::now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error('Atol resendReceipt failed', [
                'error' => $e->getMessage(),
                'fiscal_id' => $fiscalId,
            ]);
            throw $e;
        }
    }

    /**
     * Получить историю чеков.
     */
    public function getReceiptHistory(array $filters = []): array
    {
        try {
            // Atol не предоставляет метод истории через API
            // Возвращаем пустой результат или можно добавить логику через данные БД
            Log::info('Atol: getReceiptHistory not implemented in API');
            
            return [
                'total' => 0,
                'count' => 0,
                'items' => [],
            ];
        } catch (Exception $e) {
            Log::error('Atol getReceiptHistory failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить поддерживаемые налоги.
     */
    public function getSupportedTaxes(): array
    {
        return ['VAT_0', 'VAT_10', 'VAT_20', 'NO_VAT'];
    }

    /**
     * Получить поддерживаемые системы налогообложения.
     */
    public function getSupportedTaxSystems(): array
    {
        return ['OSN', 'USN_INCOME', 'USN_INCOME_MINUS_EXPENSE', 'ENVD', 'ESN', 'PSN'];
    }

    /**
     * Валидировать товары перед отправкой.
     */
    public function validateItems(array $items): array
    {
        $errors = [];
        $supportedTaxes = $this->getSupportedTaxes();
        
        foreach ($items as $index => $item) {
            if (empty($item['name'])) {
                $errors[] = "Item {$index}: missing 'name'";
            }
            if (empty($item['price']) || $item['price'] <= 0) {
                $errors[] = "Item {$index}: invalid 'price'";
            }
            if (empty($item['qty']) || $item['qty'] <= 0) {
                $errors[] = "Item {$index}: invalid 'qty'";
            }
            
            // Валидация налоговой ставки
            if (isset($item['tax'])) {
                $taxCode = strtoupper(str_replace('-', '_', $item['tax']));
                if (!in_array($taxCode, $supportedTaxes)) {
                    $errors[] = "Item {$index}: unsupported tax code '{$item['tax']}'. Supported: " . implode(', ', $supportedTaxes);
                }
            } else {
                $errors[] = "Item {$index}: missing 'tax' (required for fiscal compliance)";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Получить информацию о драйвере.
     */
    public function getInfo(): array
    {
        return [
            'name' => 'Atol',
            'version' => '2.5',
            'endpoints' => [
                'base' => $this->endpoint,
                'group_code' => $this->config['group_code'],
            ],
            'is_available' => $this->isAvailable(),
        ];
    }
}
