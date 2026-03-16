<?php

namespace App\Domains\Finances\Interfaces;

/**
 * Интерфейс драйвера фискального провайдера.
 *
 * Реализует требования ФЗ-54 (обязательная онлайн-касса).
 * Поддерживаемые провайдеры:
 * - CloudKassir (основной)
 * - Atol (резервный)
 */
interface FiscalDriverInterface
{
    /**
     * Отправить чек в фискальную систему.
     *
     * @param array $tx Данные транзакции: {
     *     'payment_id' => 'pay_xxxxx',
     *     'amount' => 100.50,
     *     'user_id' => 123,
     *     'tenant_id' => 1,
     *     'tax_system' => 'OSN|USN_INCOME|USN_INCOME_MINUS_EXPENSE|ENVD|ESN|PSN',
     *     'correlation_id' => 'uuid',
     *     'metadata' => [...]
     * }
     * @param array $items Товары/услуги: [
     *     {'name' => 'Course', 'price' => 50.25, 'qty' => 1, 'tax' => 'vat_20'},
     *     ...
     * ]
     * @return array Результат: [
     *     'fiscal_id' => 'fiscal_xxxxx',
     *     'receipt_url' => 'https://...',
     *     'status' => 'sent|received|error',
     *     'sent_at' => '2026-03-10T12:00:00Z',
     *     'error' => null
     * ]
     * @throws \Exception Если отправка чека не удалась
     */
    public function sendReceipt(array $tx, array $items): array;

    /**
     * Получить статус чека по ID.
     *
     * @param string $fiscalId ID чека в фискальной системе.
     * @return array Результат: [
     *     'fiscal_id' => 'fiscal_xxxxx',
     *     'status' => 'sent|received|error|cancelled',
     *     'receipt_url' => 'https://...',
     *     'created_at' => '2026-03-10T12:00:00Z',
     *     'error' => null
     * ]
     */
    public function getReceiptStatus(string $fiscalId): array;

    /**
     * Возврат чека (отмена/коррекция).
     *
     * @param string $fiscalId ID исходного чека в фискальной системе.
     * @param float $amount Сумма возврата.
     * @param array $data Дополнительные данные: reason, metadata, etc.
     * @return array Результат: [
     *     'refund_fiscal_id' => 'fiscal_xxxxx',
     *     'status' => 'sent|received|error',
     *     'amount' => 100.50,
     *     'sent_at' => '2026-03-10T12:05:00Z',
     *     'error' => null
     * ]
     * @throws \Exception Если возврат не удался
     */
    public function refundReceipt(string $fiscalId, float $amount, array $data = []): array;

    /**
     * Проверить связь с фискальным провайдером.
     *
     * @return bool True если провайдер доступен, false в противном случае
     */
    public function isAvailable(): bool;

    /**
     * Получить поддерживаемые типы налогов.
     *
     * @return array Список кодов налогов: ['vat0', 'vat10', 'vat20', 'no_vat']
     */
    public function getSupportedTaxes(): array;

    /**
     * Получить поддерживаемые системы налогообложения.
     *
     * @return array Список систем: ['osn', 'usn_income', 'usn_income_minus_expense', ...]
     */
    public function getSupportedTaxSystems(): array;

    /**
     * Валидировать товары перед отправкой чека.
     *
     * @param array $items Товары/услуги для проверки
     * @return array Результат: ['valid' => true, 'errors' => []]
     */
    public function validateItems(array $items): array;

    /**
     * Переотправить чек.
     *
     * @param string $fiscalId ID чека в фискальной системе.
     * @return array Результат: [
     *     'fiscal_id' => 'fiscal_xxxxx',
     *     'status' => 'resent',
     *     'sent_at' => '2026-03-10T12:00:00Z'
     * ]
     * @throws \Exception Если переотправка не удалась
     */
    public function resendReceipt(string $fiscalId): array;

    /**
     * Получить историю отправленных чеков.
     *
     * @param array $filters Фильтры: ['limit' => 100, 'offset' => 0, 'date_from' => '2026-03-01', 'date_to' => '2026-03-10']
     * @return array Результат: [
     *     'total' => 250,
     *     'count' => 100,
     *     'items' => [...]
     * ]
     */
    public function getReceiptHistory(array $filters = []): array;

    /**
     * Получить информацию о драйвере (название, версия, текущий статус).
     *
     * @return array Результат: [
     *     'name' => 'CloudKassir',
     *     'version' => '1.0',
     *     'status' => 'connected|disconnected',
     *     'last_check_at' => '2026-03-10T12:00:00Z'
     * ]
     */
    public function getInfo(): array;
}
