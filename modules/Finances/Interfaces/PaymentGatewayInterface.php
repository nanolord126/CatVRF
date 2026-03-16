<?php

namespace App\Domains\Finances\Interfaces;

/**
 * Интерфейс платёжного шлюза.
 *
 * Поддерживаемые провайдеры:
 * - Tinkoff (основной)
 * - Tochka Bank (корпоративные платежи)
 * - Sber (мобильная коммерция)
 * - SBP (быстрые платежи)
 *
 * Все методы должны быть idempotent и поддерживать retry logic.
 */
interface PaymentGatewayInterface
{
    /**
     * Инициировать платёж.
     *
     * @param array $data Данные платежа: {
     *     'amount' => 100.50,
     *     'order_id' => 'ORD-12345',
     *     'user_id' => 123,
     *     'description' => 'Course enrollment',
     *     'metadata' => ['course_id' => 1, ...]
     * }
     * @param bool $hold Удержать средства (true) или списать сразу (false).
     * @return array Результат: {
     *     'id' => 'pay_xxxxx',
     *     'url' => 'https://payment.provider.com/...',
     *     'status' => 'pending',
     *     'qr_code' => 'base64|null'
     * }
     * @throws \Exception Если инициализация платежа не удалась
     */
    public function initPayment(array $data, bool $hold = false): array;

    /**
     * Подтвердить платёж (финализировать удержание).
     *
     * @param string $paymentId ID платежа в системе шлюза.
     * @param float|null $amount Сумма (если null, использовать полную сумму платежа).
     * @return bool True если подтверждение успешно, false в противном случае
     * @throws \Exception Если подтверждение не удалось
     */
    public function capture(string $paymentId, float $amount = null): bool;

    /**
     * Возврат средств.
     *
     * @param string $paymentId ID платежа.
     * @param float $amount Сумма возврата.
     * @param array $data Дополнительные данные: reason, metadata, etc.
     * @return array Результат возврата: {
     *     'id' => 'ref_xxxxx',
     *     'status' => 'sent|received',
     *     'amount' => 100.50
     * }
     * @throws \Exception Если возврат не удался
     */
    public function refund(string $paymentId, float $amount, array $data = []): array;

    /**
     * Выплата средств пользователю (вывод денег).
     *
     * @param array $receiver Данные получателя: {
     *     'user_id' => 123,
     *     'card' => '****1234',
     *     'phone' => '+7900...',
     *     'email' => 'user@example.com'
     * }
     * @param float $amount Сумма выплаты.
     * @param array $data Дополнительные данные: description, metadata
     * @return array Результат: {'id' => 'payout_xxxxx', 'status' => 'sent'}
     * @throws \Exception Если выплата не удалась
     */
    public function payout(array $receiver, float $amount, array $data = []): array;

    /**
     * Обработка вебхука платёжной системы.
     *
     * @param array $payload Данные вебхука от платёжного провайдера.
     * @return array Обработанные данные: {
     *     'PaymentId' => 'pay_xxxxx',
     *     'Status' => 'CONFIRMED|PENDING|FAILED',
     *     'Amount' => 10050,
     *     'OrderId' => 'ORD-12345'
     * }
     */
    public function handleWebhook(array $payload): array;

    /**
     * Генерировать динамический QR-код (SBP/Альфа-банк).
     *
     * @param array $data Данные: {
     *     'amount' => 100.50,
     *     'phone' => '+79001234567',
     *     'order_id' => 'ORD-12345',
     *     'recipient_name' => 'Shop Name'
     * }
     * @return array Результат: {
     *     'qr_code' => 'base64_encoded_image',
     *     'url' => 'https://sbp.provider.com/...',
     *     'expires_at' => '2026-03-10T13:00:00Z'
     * }
     */
    public function generateUniversalQR(array $data): array;

    /**
     * Получить статус платежа.
     *
     * @param string $paymentId ID платежа.
     * @return array Результат: {
     *     'id' => 'pay_xxxxx',
     *     'status' => 'pending|confirmed|failed|refunded',
     *     'amount' => 10050,
     *     'created_at' => '2026-03-10T12:00:00Z'
     * }
     */
    public function getPaymentStatus(string $paymentId): array;

    /**
     * Получить статус SBP платежа (быстрый метод).
     *
     * @param string $paymentId ID платежа.
     * @return string Статус: 'pending', 'confirmed', 'failed', 'refunded'
     */
    public function getSbpStatus(string $paymentId): string;

    /**
     * Токенизировать карту для повторного платежа.
     *
     * @param array $data Данные карты: {
     *     'card_number' => '4111111111111111',
     *     'exp_month' => 12,
     *     'exp_year' => 2026,
     *     'cvv' => '123'
     * }
     * @return array Результат: {
     *     'token' => 'tok_xxxxx',
     *     'masked_card' => '****1111',
     *     'card_brand' => 'VISA',
     *     'exp_month' => 12,
     *     'exp_year' => 2026
     * }
     * @throws \Exception Если токенизация не удалась
     */
    public function tokenizeCard(array $data): array;

    /**
     * Списание по сохранённому токену (Autopay/Повторный платёж).
     *
     * @param string $token Токен карты.
     * @param float $amount Сумма платежа.
     * @param array $data Дополнительные данные: {
     *     'order_id' => 'ORD-12345',
     *     'description' => '...',
     *     'user_id' => 123,
     *     'metadata' => [...]
     * }
     * @return array Результат платежа: {
     *     'id' => 'pay_xxxxx',
     *     'status' => 'confirmed|pending|failed',
     *     'amount' => 10050
     * }
     * @throws \Exception Если платёж не удался
     */
    public function chargeByToken(string $token, float $amount, array $data): array;

    /**
     * Проверить здоровье и доступность платёжного шлюза.
     *
     * @return array Результат: {
     *     'status' => 'operational|degraded|offline',
     *     'provider' => 'tinkoff|yandex|stripe',
     *     'last_check_at' => '2026-03-10T12:00:00Z',
     *     'message' => 'All systems operational|...'
     * }
     */
    public function healthCheck(): array;

    /**
     * Получить информацию о шлюзе.
     *
     * @return array Результат: {
     *     'name' => 'Tinkoff',
     *     'version' => '1.0',
     *     'supports' => ['sbp', 'cards', 'qr_codes'],
     *     'currencies' => ['RUB', 'USD'],
     *     'min_amount' => 1.00,
     *     'max_amount' => 999999.99
     * }
     */
    public function getInfo(): array;
}
