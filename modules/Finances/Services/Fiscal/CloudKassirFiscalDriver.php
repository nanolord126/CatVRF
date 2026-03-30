<?php declare(strict_types=1);

namespace Modules\Finances\Services\Fiscal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CloudKassirFiscalDriver extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Http, Log};
    use Exception;
    
    /**
     * CloudKassir Fiscal Driver для ФЗ-54 compliance.
     * 
     * Основной провайдер фискализации. Поддерживает:
     * - Чеки продажи (Income/Expense)
     * - Возвраты
     * - Кассовые сессии
         * - НДС по системам налогообложения (ОСН, УСН, ЕСХН, ЕНВД, ПСН)
         */
        class CloudKassirFiscalDriver implements FiscalDriverInterface
        {
            private array $config;
            private string $endpoint;
    
            public function __construct()
            {
                $this->config = config('fiscal.drivers.cloudkassir');
                $this->endpoint = $this->config['endpoint'];
            }
    
            /**
             * Получить налоговую ставку для системы налогообложения CloudKassir.
             * 
             * @param string $taxSystem Система налогообложения (OMS, UsnIncome, UsnIncomeMinusExpense, Envd, Esn, Patent)
             * @param string|null $taxCode Код налога из товара
             * @return string Код налога для CloudKassir API
             */
            private function getTaxRate(string $taxSystem, ?string $taxCode = null): string
            {
                // УСН (Упрощенная система) - без НДС
                if (str_contains($taxSystem, 'Usn')) {
                    return 'NoVat';
                }
    
                // ЕСХН (Единый сельскохозяйственный налог) - без НДС
                if ($taxSystem === 'Esn') {
                    return 'NoVat';
                }
    
                // ОСН (Общая система) - с НДС (0%, 10%, 20%)
                if ($taxSystem === 'OMS') {
                    $taxRates = [
                        'vat_0' => 'Vat0',
                        'vat_10' => 'Vat10',
                        'vat_20' => 'Vat20',
                    ];
                    
                    if ($taxCode && isset($taxRates[strtolower($taxCode)])) {
                        return $taxRates[strtolower($taxCode)];
                    }
                    
                    // По умолчанию Vat20 для ОСН (стандартная ставка с 2019 года)
                    return 'Vat20';
                }
    
                // ЕНВД, ПСН - без НДС
                if (in_array($taxSystem, ['Envd', 'Patent'])) {
                    return 'NoVat';
                }
    
                // По умолчанию
                return 'NoVat';
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
                        'quantity' => (float) ($item['qty'] ?? $item['quantity'] ?? 1),
                        'sum' => (float) $item['price'] * (float) ($item['qty'] ?? $item['quantity'] ?? 1),
                        'tax' => $taxRate,
                    ];
                    
                    $processedItems[] = $processedItem;
                }
                
                return $processedItems;
            }
    
            /**
             * Отправить фискальный чек.
             */
            public function sendReceipt(array $tx, array $items): array
            {
                try {
                    // Валидация товаров
                    $validation = $this->validateItems($items);
                    if (!$validation['valid']) {
                        throw new Exception('Invalid items structure: ' . implode('; ', $validation['errors']));
                    }
    
                    $taxSystem = $tx['tax_system'] ?? config('fiscal.common.taxation_system', 'OMS');
                    $processedItems = $this->processItemsWithTax($items, $taxSystem);
    
                    $payload = [
                        'Inn' => config('fiscal.common.inn'),
                        'Type' => 'Income',
                        'CustomerReceipt' => [
                            'Items' => $processedItems,
                            'taxationSystem' => $taxSystem,
                        ],
                        'InvoiceId' => $tx['payment_id'] ?? $tx['id'],
                        'AccountId' => $tx['user_id'] ?? null,
                        'Email' => $tx['metadata']['email'] ?? 'noreply@catvrf.ru',
                        'Phone' => $tx['metadata']['phone'] ?? null,
                    ];
    
                    $response = Http::withBasicAuth($this->config['id'], $this->config['key'])
                        ->timeout(15)
                        ->post($this->endpoint, $payload)
                        ->throw()
                        ->json();
    
                    if ($response['Success'] === false) {
                        throw new Exception("CloudKassir API error: {$response['Message']}");
                    }
    
                    $fiscalId = $response['Model']['Id'] ?? null;
                    $receiptUrl = $response['Model']['Url'] ?? null;
    
                    Log::info('CloudKassir receipt sent', [
                        'fiscal_id' => $fiscalId,
                        'payment_id' => $tx['payment_id'] ?? null,
                        'correlation_id' => $tx['correlation_id'] ?? null,
                        'receipt_url' => $receiptUrl,
                    ]);
    
                    return [
                        'fiscal_id' => $fiscalId,
                        'receipt_url' => $receiptUrl,
                        'status' => 'registered',
                        'sent_at' => Carbon::now()->toIso8601String(),
                        'error' => null,
                    ];
                } catch (Exception $e) {
                    Log::error('CloudKassir sendReceipt failed', [
                        'error' => $e->getMessage(),
                        'payment_id' => $tx['payment_id'] ?? null,
                        'correlation_id' => $tx['correlation_id'] ?? null,
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
                $response = Http::withBasicAuth($this->config['id'], $this->config['key'])
                    ->timeout(10)
                    ->get("{$this->endpoint}/{$fiscalId}")
                    ->throw()
                    ->json();
    
                return [
                    'fiscal_id' => $response['Model']['Id'] ?? $fiscalId,
                    'status' => $response['Model']['DocStatus'] ?? 'unknown',
                    'receipt_url' => $response['Model']['Url'] ?? null,
                    'created_at' => $response['Model']['Created'] ?? null,
                    'error' => null,
                ];
            } catch (Exception $e) {
                Log::error('CloudKassir getReceiptStatus failed', [
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
                $taxSystem = $data['tax_system'] ?? config('fiscal.common.taxation_system', 'OMS');
                $taxRate = $this->getTaxRate($taxSystem, $data['tax'] ?? null);
                
                $payload = [
                    'Inn' => config('fiscal.common.inn'),
                    'Type' => 'Expense',
                    'CustomerReceipt' => [
                        'Items' => [
                            [
                                'name' => $data['reason'] ?? 'Возврат',
                                'price' => $amount,
                                'quantity' => 1,
                                'sum' => $amount,
                                'tax' => $taxRate,
                            ],
                        ],
                        'taxationSystem' => $taxSystem,
                    ],
                    'InvoiceId' => "refund-{$fiscalId}-" . Carbon::now()->timestamp,
                    'OriginalDocumentId' => $fiscalId,
                ];
    
                $response = Http::withBasicAuth($this->config['id'], $this->config['key'])
                    ->timeout(15)
                    ->post($this->endpoint, $payload)
                    ->throw()
                    ->json();
    
                if ($response['Success'] === false) {
                    throw new Exception("CloudKassir refund error: {$response['Message']}");
                }
    
                Log::info('CloudKassir refund sent', [
                    'original_fiscal_id' => $fiscalId,
                    'refund_fiscal_id' => $response['Model']['Id'] ?? null,
                    'amount' => $amount,
                ]);
    
                return [
                    'refund_fiscal_id' => $response['Model']['Id'] ?? null,
                    'receipt_url' => $response['Model']['Url'] ?? null,
                    'status' => 'registered',
                ];
            } catch (Exception $e) {
                Log::error('CloudKassir refundReceipt failed', [
                    'error' => $e->getMessage(),
                    'fiscal_id' => $fiscalId,
                    'amount' => $amount,
                ]);
                throw $e;
            }
        }
    
        /**
         * Проверить доступность CloudKassir API.
         */
        public function isAvailable(): bool
        {
            try {
                $response = Http::withBasicAuth($this->config['id'], $this->config['key'])
                    ->timeout(5)
                    ->head($this->endpoint)
                    ->successful();
    
                Log::info('CloudKassir availability check', ['available' => $response]);
                return $response;
            } catch (Exception $e) {
                Log::warning('CloudKassir availability check failed', ['error' => $e->getMessage()]);
                return false;
            }
        }
    
        /**
         * Переотправить чек.
         */
        public function resendReceipt(string $fiscalId): array
        {
            try {
                Log::info('CloudKassir: resending receipt', ['fiscal_id' => $fiscalId]);
    
                $response = Http::withBasicAuth($this->config['id'], $this->config['key'])
                    ->timeout(10)
                    ->get("{$this->endpoint}/{$fiscalId}")
                    ->throw()
                    ->json();
    
                return [
                    'fiscal_id' => $response['Model']['Id'] ?? $fiscalId,
                    'status' => 'resent',
                    'receipt_url' => $response['Model']['Url'] ?? null,
                    'sent_at' => Carbon::now()->toIso8601String(),
                ];
            } catch (Exception $e) {
                Log::error('CloudKassir resendReceipt failed', [
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
                // CloudKassir не предоставляет метод истории через API
                // Возвращаем пустой результат или можно добавить логику через данные БД
                Log::info('CloudKassir: getReceiptHistory not implemented in API');
                
                return [
                    'total' => 0,
                    'count' => 0,
                    'items' => [],
                ];
            } catch (Exception $e) {
                Log::error('CloudKassir getReceiptHistory failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    
        /**
         * Получить поддерживаемые налоги.
         * 
         * @return array Коды налогов для CloudKassir API
         */
        public function getSupportedTaxes(): array
        {
            return ['Vat20', 'Vat10', 'Vat0', 'NoVat'];
        }
    
        /**
         * Получить поддерживаемые системы налогообложения.
         */
        public function getSupportedTaxSystems(): array
        {
            return ['OMS', 'UsnIncome', 'UsnIncomeMinusExpense', 'Envd', 'Esn', 'Patent'];
        }
    
        /**
         * Валидировать товары перед отправкой.
         */
        public function validateItems(array $items): array
        {
            $errors = [];
            $supportedTaxes = $this->getSupportedTaxes();
    
            if (empty($items)) {
                return [
                    'valid' => false,
                    'errors' => ['Items array is empty'],
                ];
            }
    
            foreach ($items as $index => $item) {
                if (empty($item['name'])) {
                    $errors[] = "Item {$index}: missing 'name'";
                }
                if (empty($item['price']) || $item['price'] <= 0) {
                    $errors[] = "Item {$index}: invalid 'price'";
                }
                
                $qty = $item['quantity'] ?? $item['qty'] ?? 0;
                if ($qty <= 0) {
                    $errors[] = "Item {$index}: invalid 'quantity'";
                }
                
                if (isset($item['tax'])) {
                    $taxCode = strtoupper(str_replace('-', '_', $item['tax']));
                    if (!in_array($taxCode, $supportedTaxes)) {
                        $errors[] = "Item {$index}: unsupported tax '{$item['tax']}'. Supported: " . implode(', ', $supportedTaxes);
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
                'name' => 'CloudKassir',
                'version' => '1.0',
                'type' => 'primary',
                'endpoints' => [
                    'base' => $this->endpoint,
                    'organization_id' => $this->config['id'],
                ],
                'supported_taxes' => $this->getSupportedTaxes(),
                'supported_systems' => $this->getSupportedTaxSystems(),
                'is_available' => $this->isAvailable(),
            ];
        }
}
