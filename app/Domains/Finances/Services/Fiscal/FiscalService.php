<?php

namespace App\Domains\Finances\Services\Fiscal;

use App\Domains\Finances\Interfaces\{FiscalServiceInterface, FiscalDriverInterface};
use App\Domains\Finances\Services\Fiscal\{CloudKassirFiscalDriver, AtolFiscalDriver};
use Illuminate\Support\Facades\{Cache, Log};
use Exception;

/**
 * Сервис управления фискализацией (ФЗ-54).
 * 
 * Обеспечивает:
 * - Выбор оптимального драйвера фискализации (CloudKassir/Atol)
 * - Отправку чеков с резервной системой
 * - Отслеживание статуса чеков
 * - Повторную отправку при ошибках
 * - Поддержку НДС по системам налогообложения:
 *   * ОСН (Общая система): НДС 0%, 10%, 18%, 20%
 *   * УСН (Упрощенная система): без НДС
 *   * ЕСХН (Единый сельскохозяйственный налог): без НДС
 *   * ЕНВД (Единый налог на вмененный доход): без НДС
 *   * ПСН (Патентная система): без НДС
 * 
 * Каждый драйвер содержит:
 * - getTaxRate(): расчет налога по системе налогообложения
 * - processItemsWithTax(): обработка товаров с добавлением налогов
 * - validateItems(): валидация товаров с проверкой налоговых кодов
 */
class FiscalService implements FiscalServiceInterface
{
    private array $drivers;
    private string $defaultDriver;

    public function __construct()
    {
        $this->defaultDriver = config('fiscal.default', 'cloudkassir');
        $this->drivers = [
            'cloudkassir' => CloudKassirFiscalDriver::class,
            'atol' => AtolFiscalDriver::class,
        ];
    }

    /**
     * Получить экземпляр драйвера фискализации.
     */
    public function getDriver(string $name = null): FiscalDriverInterface
    {
        $name = $name ?? $this->defaultDriver;

        if (!isset($this->drivers[$name])) {
            throw new Exception("Unknown fiscal driver: {$name}");
        }

        $driverClass = $this->drivers[$name];
        return new $driverClass();
    }

    /**
     * Отправить фискальный чек с резервной системой.
     */
    public function sendReceipt(array $tx, array $items): array
    {
        // Сначала пытаемся основной драйвер
        try {
            $driver = $this->getDriver($this->defaultDriver);
            
            if (!$driver->isAvailable()) {
                Log::warning("Primary fiscal driver {$this->defaultDriver} unavailable, trying fallback");
                return $this->sendReceiptWithFallback($tx, $items);
            }

            $result = $driver->sendReceipt($tx, $items);
            
            Log::info('Fiscal receipt sent successfully', [
                'driver' => $this->defaultDriver,
                'payment_id' => $tx['payment_id'] ?? null,
                'fiscal_id' => $result['fiscal_id'] ?? null,
            ]);

            return $result;
        } catch (Exception $e) {
            Log::warning("Primary fiscal driver failed: {$e->getMessage()}", [
                'payment_id' => $tx['payment_id'] ?? null,
            ]);
            
            return $this->sendReceiptWithFallback($tx, $items);
        }
    }

    /**
     * Отправить чек с резервным драйвером.
     */
    private function sendReceiptWithFallback(array $tx, array $items): array
    {
        $fallbackDriver = $this->defaultDriver === 'cloudkassir' ? 'atol' : 'cloudkassir';
        
        try {
            $driver = $this->getDriver($fallbackDriver);
            
            if (!$driver->isAvailable()) {
                throw new Exception("Fallback driver {$fallbackDriver} also unavailable");
            }

            $result = $driver->sendReceipt($tx, $items);
            
            Log::info('Fiscal receipt sent via fallback driver', [
                'driver' => $fallbackDriver,
                'payment_id' => $tx['payment_id'] ?? null,
                'fiscal_id' => $result['fiscal_id'] ?? null,
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('Both fiscal drivers failed', [
                'primary' => $this->defaultDriver,
                'fallback' => $fallbackDriver,
                'payment_id' => $tx['payment_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить статус фискального чека.
     */
    public function getReceiptStatus(string $fiscalId): array
    {
        try {
            // Кэшируем результат на 5 минут
            return Cache::remember("fiscal_status_{$fiscalId}", 300, function () use ($fiscalId) {
                $driver = $this->getDriver($this->defaultDriver);
                return $driver->getReceiptStatus($fiscalId);
            });
        } catch (Exception $e) {
            Log::error('Get receipt status failed', [
                'fiscal_id' => $fiscalId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Отправить чек возврата.
     * 
     * @param string $fiscalId Номер исходного чека
     * @param float $amount Сумма возврата
     * @param array $data Дополнительные данные: tax_system, tax, reason, etc.
     * @return array Результат возврата
     */
    public function refundReceipt(string $fiscalId, float $amount, array $data = []): array
    {
        try {
            $driver = $this->getDriver($this->defaultDriver);
            
            if (!$driver->isAvailable()) {
                $driver = $this->getDriver('atol');
            }

            $result = $driver->refundReceipt($fiscalId, $amount, $data);
            
            Log::info('Fiscal refund sent', [
                'original_fiscal_id' => $fiscalId,
                'amount' => $amount,
                'refund_fiscal_id' => $result['refund_fiscal_id'] ?? null,
                'tax_system' => $data['tax_system'] ?? null,
            ]);

            // Инвалидируем кэш статуса оригинального чека
            Cache::forget("fiscal_status_{$fiscalId}");

            return $result;
        } catch (Exception $e) {
            Log::error('Refund receipt failed', [
                'fiscal_id' => $fiscalId,
                'amount' => $amount,
                'error' => $e->getMessage(),
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
            $driver = $this->getDriver($this->defaultDriver);
            
            // Попытка получить из основного драйвера (если поддерживает)
            if (method_exists($driver, 'getReceiptHistory')) {
                return $driver->getReceiptHistory($filters);
            }

            Log::info('Receipt history not available in fiscal driver');
            return [];
        } catch (Exception $e) {
            Log::error('Get receipt history failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Повторно отправить неудачный чек.
     */
    public function resendReceipt(string $fiscalId): array
    {
        try {
            Log::info('Resending fiscal receipt', [
                'fiscal_id' => $fiscalId,
            ]);

            // Инвалидируем кэш
            Cache::forget("fiscal_status_{$fiscalId}");

            $driver = $this->getDriver($this->defaultDriver);
            if (!method_exists($driver, 'resendReceipt')) {
                throw new Exception("Resend receipt not supported by {$this->defaultDriver}");
            }

            return $driver->resendReceipt($fiscalId);
        } catch (Exception $e) {
            Log::error('Resend fiscal receipt failed', [
                'fiscal_id' => $fiscalId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Проверить здоровье фискальной системы.
     */
    public function healthCheck(): array
    {
        $results = [];

        foreach ($this->drivers as $name => $driverClass) {
            try {
                $driver = new $driverClass();
                $results[$name] = [
                    'available' => $driver->isAvailable(),
                    'info' => $driver->getInfo(),
                ];
            } catch (Exception $e) {
                Log::warning("Health check failed for {$name}", ['error' => $e->getMessage()]);
                $results[$name] = [
                    'available' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Получить информацию о фискальной системе.
     */
    public function getInfo(): array
    {
        return [
            'default_driver' => $this->defaultDriver,
            'drivers' => array_keys($this->drivers),
            'health' => $this->healthCheck(),
            'config' => [
                'inn' => config('fiscal.common.inn'),
                'taxation_system' => config('fiscal.common.taxation_system'),
                'default_tax' => config('fiscal.common.default_tax'),
            ],
        ];
    }
}
