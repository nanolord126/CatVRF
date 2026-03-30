<?php declare(strict_types=1);

namespace Modules\Finances\Interfaces;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FiscalServiceInterface extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Отправить чек в фискальную систему.
         *
         * @param array $transactionData Данные транзакции: {
         *     'payment_id' => 'pay_xxxxx',
         *     'amount' => 100.50,
         *     'tax_system' => 'OSN|USN_INCOME|USN_INCOME_MINUS_EXPENSE|ENVD|ESN|PSN',
         *     'correlation_id' => 'uuid',
         *     'metadata' => {
         *         'email' => 'user@example.com',
         *         'phone' => '+7900...'
         *     }
         * }
         * @param array $items Товары/услуги: [
         *     {'name' => 'Product', 'price' => 50.25, 'qty' => 1, 'tax' => 'vat_20'},
         *     ...
         * ]
         * @return array Результат: {
         *     'fiscal_id' => 'fiscal_xxxxx',
         *     'receipt_url' => 'https://...',
         *     'status' => 'sent|received|error',
         *     'sent_at' => '2026-03-10T12:00:00Z'
         * }
         * @throws \Exception Если отправка чека не удалась
         */
        public function sendReceipt(array $transactionData, array $items): array;
    
        /**
         * Получить статус чека.
         *
         * @param string $fiscalId ID чека в фискальной системе.
         * @return array Статус: {
         *     'fiscal_id' => 'fiscal_xxxxx',
         *     'status' => 'sent|received|error|cancelled',
         *     'receipt_url' => 'https://...',
         *     'created_at' => '2026-03-10T12:00:00Z',
         *     'error' => null
         * }
         */
        public function getReceiptStatus(string $fiscalId): array;
    
        /**
         * Возврат чека (для отмены или возврата платежа).
         *
         * @param string $fiscalId ID исходного чека.
         * @param float $amount Сумма возврата.
         * @param array $data Данные возврата: {
         *     'reason' => 'Customer refund request',
         *     'tax_system' => 'OSN|USN_INCOME|USN_INCOME_MINUS_EXPENSE|ENVD|ESN|PSN',
         *     'tax' => 'vat_20|vat_10|vat_0|no_vat',
         *     'correlation_id' => 'uuid'
         * }
         * @return array Результат возврата: {
         *     'refund_fiscal_id' => 'fiscal_xxxxx',
         *     'status' => 'sent|received|error',
         *     'amount' => 100.50,
         *     'sent_at' => '2026-03-10T12:05:00Z'
         * }
         * @throws \Exception Если возврат не удался
         */
        public function refundReceipt(string $fiscalId, float $amount, array $data = []): array;
    
        /**
         * Проверить здоровье и доступность фискальной системы.
         *
         * @return array Результат: {
         *     'status' => 'operational|degraded|offline',
         *     'provider' => 'cloudkassir|atol|yandex',
         *     'last_check_at' => '2026-03-10T12:00:00Z',
         *     'message' => 'All systems operational|...'
         * }
         */
        public function healthCheck(): array;
    
        /**
         * Получить информацию о фискальной системе.
         *
         * @return array Результат: {
         *     'name' => 'CloudKassir',
         *     'version' => '1.0',
         *     'supported_taxes' => ['vat0', 'vat10', 'vat18', 'vat20'],
         *     'supported_tax_systems' => ['osn', 'usn_income', 'usn_income_minus_expense'],
         *     'organization_name' => 'Company Name',
         *     'inn' => '7710....',
         *     'authenticated' => true
         * }
         */
        public function getInfo(): array;
    
        /**
         * Получить историю отправленных чеков.
         *
         * @param array $filters Фильтры: {
         *     'limit' => 100,
         *     'offset' => 0,
         *     'date_from' => '2026-03-01',
         *     'date_to' => '2026-03-10'
         * }
         * @return array Результат: {
         *     'total' => 250,
         *     'count' => 100,
         *     'items' => [...]
         * }
         */
        public function getReceiptHistory(array $filters = []): array;
    
        /**
         * Повторно отправить чек.
         *
         * @param string $fiscalId ID чека, который нужно переотправить.
         * @return array Результат: {
         *     'fiscal_id' => 'fiscal_xxxxx',
         *     'status' => 'sent|already_sent',
         *     'sent_at' => '2026-03-10T12:00:00Z'
         * }
         * @throws \Exception Если переотправка не удалась
         */
        public function resendReceipt(string $fiscalId): array;
}
