<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Contracts;

/**
 * Interface for OFD (Fiscal Data Operator).
 *
 * Implements integration with OFD providers for 54-FZ:
 * - Tensor (OFD.ru)
 * - Kontur
 * - ATOL (ATOL Online)
 * - Yandex.Kassa (as OFD)
 *
 * Responsible for:
 * - Sending receipts to FNS
 * - Getting fiscal signs
 * - Correcting receipts
 * - Reporting
 */
interface OFDInterface
{
    /**
     * Send receipt to OFD.
     *
     * @param array<string, mixed> $receiptData receipt data (items, amounts, taxes)
     * @param string $correlationId correlation_id for audit
     * @return array{fiscal_sign: string, fiscal_document_number: int, fiscal_document_attribute: int, ofd_response: array<string, mixed>}
     */
    public function sendReceipt(array $receiptData, string $correlationId): array;

    /**
     * Send correction receipt.
     *
     * @param array<string, mixed> $correctionData correction data
     * @param string $correlationId correlation_id for audit
     * @return array{fiscal_sign: string, fiscal_document_number: int, ofd_response: array<string, mixed>}
     */
    public function sendCorrection(array $correctionData, string $correlationId): array;

    /**
     * Get receipt status by fiscal sign.
     *
     * @param string $fiscalSign fiscal sign of receipt
     * @param string $correlationId correlation_id for audit
     * @return array{status: string, received_at: string|null, ofd_response: array<string, mixed>}
     */
    public function getReceiptStatus(string $fiscalSign, string $correlationId): array;

    /**
     * Get KKT (cash register) information.
     *
     * @return array{kkt_reg_number: string, ktt_serial: string, fn_number: string, ofd_response: array<string, mixed>}
     */
    public function getKKTInfo(): array;

    /**
     * OFD provider.
     *
     * @return string
     */
    public function getProvider(): string;
}
