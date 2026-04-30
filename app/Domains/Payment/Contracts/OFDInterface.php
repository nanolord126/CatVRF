<?php

declare(strict_types=1);

namespace App\Domains\Payment\Contracts;

/**
 * Интерфейс для ОФД (Оператор фискальных данных).
 *
 * Реализует интеграцию с ОФД-провайдерами для 54-ФЗ:
 * - Тензор (OFD.ru)
 * - Контур (Kontur)
 * - АТОЛ (ATOL Online)
 * - Яндекс.Касса (как ОФД)
 *
 * Отвечает за:
 * - Отправку чеков в ФНС
 * - Получение фискальных признаков
 * - Коррекцию чеков
 * - Отчётность
 */
interface OFDInterface
{
    /**
     * Отправить чек в ОФД.
     *
     * @param  array<string, mixed>  $receiptData  данные чека (товары, суммы, налоги)
     * @param  string  $correlationId  correlation_id для аудита
     * @return array{fiscal_sign: string, fiscal_document_number: int, fiscal_document_attribute: int, ofd_response: array<string, mixed>}
     */
    public function sendReceipt(array $receiptData, string $correlationId): array;

    /**
     * Отправить чек коррекции.
     *
     * @param  array<string, mixed>  $correctionData  данные коррекции
     * @param  string  $correlationId  correlation_id для аудита
     * @return array{fiscal_sign: string, fiscal_document_number: int, ofd_response: array<string, mixed>}
     */
    public function sendCorrection(array $correctionData, string $correlationId): array;

    /**
     * Получить статус чека по фискальному признаку.
     *
     * @param  string  $fiscalSign  фискальный признак чека
     * @param  string  $correlationId  correlation_id для аудита
     * @return array{status: string, received_at: string|null, ofd_response: array<string, mixed>}
     */
    public function getReceiptStatus(string $fiscalSign, string $correlationId): array;

    /**
     * Получить информацию о ККТ (контрольно-кассовой технике).
     *
     * @return array{kkt_reg_number: string, ktt_serial: string, fn_number: string, ofd_response: array<string, mixed>}
     */
    public function getKKTInfo(): array;

    /**
     * Провайдер ОФД.
     *
     * @return string
     */
    public function getProvider(): string;
}
