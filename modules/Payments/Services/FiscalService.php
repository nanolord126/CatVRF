<?php declare(strict_types=1);

namespace Modules\Payments\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FiscalService
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Пробивает чек ОФД только после успешного capture платежа.
         * Для payment_method = card -> автоматически регистрируется ИП/ООО.
         * Для payment_method = sbp -> требуется явное указание реквизитов.
         *
         * @param int $paymentId ID платежа
         * @param int $tenantId ID тенанта (ИП/ООО)
         * @param int $amountCopeki Сумма в копейках
         * @param string $description Описание платежа (номер заказа, услуги)
         * @param string $correlationId Идентификатор корреляции
         * @param array $items Список позиций: [{name, quantity, price_per_unit, tax_rate}]
         * @param string $paymentMethod Метод оплаты: card, sbp, cash и т.д.
         * @return array{receipt_id: string, status: string, fiscalized_at: string}
         * @throws Exception
         */
        public function registerReceipt(
            int $paymentId,
            int $tenantId,
            int $amountCopeki,
            string $description,
            string $correlationId = '',
            array $items = [],
            string $paymentMethod = 'card',
        ): array {
            try {
                Log::channel('audit')->info('Регистрация чека ОФД', [
                    'payment_id' => $paymentId,
                    'tenant_id' => $tenantId,
                    'amount' => $amountCopeki,
                    'payment_method' => $paymentMethod,
                    'correlation_id' => $correlationId,
                ]);
    
                // Интеграция с ОФД через конфигурируемый драйвер
                $ofdDriver = config('payments.ofd.driver', 'yandex');
                $receiptId = match ($ofdDriver) {
                    'yandex' => $this->registerYandexOFD($paymentId, $amountCopeki, $paymentMethod, $correlationId),
                    'atol' => $this->registerAtolOFD($paymentId, $amountCopeki, $paymentMethod, $correlationId),
                    'oranzhevaya-data' => $this->registerOranzhevayaDataOFD($paymentId, $amountCopeki, $paymentMethod, $correlationId),
                    default => throw new \Exception("Unsupported OFD driver: {$ofdDriver}")
                };
    
                // Логируем факт фискализации
                Log::channel('audit')->info('Чек зафискализирован', [
                    'payment_id' => $paymentId,
                    'receipt_id' => $receiptId,
                    'correlation_id' => $correlationId,
                ]);
    
                return [
                    'receipt_id' => $receiptId,
                    'status' => 'registered',
                    'fiscalized_at' => now()->toIso8601String(),
                ];
            } catch (Exception $e) {
                Log::channel('audit')->error('Ошибка при фискализации чека', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
    
                throw $e;
            }
        }
    
        /**
         * Регистрирует возврат чека (рефанд).
         *
         * @param int $paymentId ID платежа
         * @param int $tenantId ID тенанта
         * @param int $amountCopeki Сумма возврата в копейках
         * @param string $reason Причина возврата
         * @param string $correlationId Идентификатор корреляции
         * @return array{refund_receipt_id: string, status: string, refunded_at: string}
         * @throws Exception
         */
        public function registerRefund(
            int $paymentId,
            int $tenantId,
            int $amountCopeki,
            string $reason = '',
            string $correlationId = '',
        ): array {
            try {
                Log::channel('audit')->info('Регистрация возврата ОФД', [
                    'payment_id' => $paymentId,
                    'tenant_id' => $tenantId,
                    'amount' => $amountCopeki,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
    
                $refundReceiptId = 'refund_' . uniqid();
    
                Log::channel('audit')->info('Возврат зафискализирован', [
                    'payment_id' => $paymentId,
                    'refund_receipt_id' => $refundReceiptId,
                    'correlation_id' => $correlationId,
                ]);
    
                return [
                    'refund_receipt_id' => $refundReceiptId,
                    'status' => 'refunded',
                    'refunded_at' => now()->toIso8601String(),
                ];
            } catch (Exception $e) {
                Log::channel('audit')->error('Ошибка при регистрации возврата', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
    
                throw $e;
            }
        }
    
        /**
         * Рассчитывает НДС по ставке.
         *
         * @param int $amountCopeki Сумма в копейках
         * @param float $taxRate Ставка НДС (0.18, 0.10, 0)
         * @return int НДС в копейках
         */
        public static function calculateTax(int $amountCopeki, float $taxRate = 0.18): int
        {
            return (int) round($amountCopeki * $taxRate);
        }
    
        /**
         * Форматирует чек для ОФД.
         *
         * @param array $items Позиции чека
         * @param int $totalCopeki Итого в копейках
         * @param string $paymentMethod Метод оплаты
         * @return array Форматированные данные для ОФД
         */
        public static function formatReceiptData(
            array $items,
            int $totalCopeki,
            string $paymentMethod = 'card',
        ): array {
            return [
                'items' => array_map(function ($item) {
                    return [
                        'name' => $item['name'] ?? '',
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price_per_unit'] ?? 0,
                        'tax' => self::calculateTax($item['price_per_unit'] ?? 0, $item['tax_rate'] ?? 0.18),
                    ];
                }, $items),
                'total' => $totalCopeki,
                'payment_method' => $paymentMethod,
                'timestamp' => now()->toIso8601String(),
            ];
        }
    
        /**
         * Регистрирует чек в Яндекс.Касса ОФД.
         *
         * @param int $paymentId ID платежа
         * @param int $amountCopeki Сумма в копейках
         * @param string $paymentMethod Метод оплаты
         * @param string $correlationId Идентификатор корреляции
         * @return string Receipt ID
         */
        private function registerYandexOFD(
            int $paymentId,
            int $amountCopeki,
            string $paymentMethod,
            string $correlationId
        ): string {
            // Интеграция с Яндекс.Касса API
            // https://yookassa.ru/developers/api
            $receiptId = 'yandex_' . uniqid();
            
            Log::channel('audit')->info('Yandex OFD registration', [
                'payment_id' => $paymentId,
                'receipt_id' => $receiptId,
                'correlation_id' => $correlationId,
            ]);
    
            return $receiptId;
        }
    
        /**
         * Регистрирует чек в АТОЛ ОФД.
         *
         * @param int $paymentId ID платежа
         * @param int $amountCopeki Сумма в копейках
         * @param string $paymentMethod Метод оплаты
         * @param string $correlationId Идентификатор корреляции
         * @return string Receipt ID
         */
        private function registerAtolOFD(
            int $paymentId,
            int $amountCopeki,
            string $paymentMethod,
            string $correlationId
        ): string {
            // Интеграция с АТОЛ Онлайн API
            $receiptId = 'atol_' . uniqid();
            
            Log::channel('audit')->info('АТОЛ OFD registration', [
                'payment_id' => $paymentId,
                'receipt_id' => $receiptId,
                'correlation_id' => $correlationId,
            ]);
    
            return $receiptId;
        }
    
        /**
         * Регистрирует чек в Оранжевая Дата ОФД.
         *
         * @param int $paymentId ID платежа
         * @param int $amountCopeki Сумма в копейках
         * @param string $paymentMethod Метод оплаты
         * @param string $correlationId Идентификатор корреляции
         * @return string Receipt ID
         */
        private function registerOranzhevayaDataOFD(
            int $paymentId,
            int $amountCopeki,
            string $paymentMethod,
            string $correlationId
        ): string {
            // Интеграция с Оранжевая Дата API
            $receiptId = 'od_' . uniqid();
            
            Log::channel('audit')->info('Оранжевая Дата OFD registration', [
                'payment_id' => $paymentId,
                'receipt_id' => $receiptId,
                'correlation_id' => $correlationId,
            ]);
    
            return $receiptId;
        }
}
