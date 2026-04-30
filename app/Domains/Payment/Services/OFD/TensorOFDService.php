<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\OFD;

use App\Domains\Payment\Contracts\OFDInterface;
use App\Services\AuditService;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Http\Client\PendingRequest;
use Psr\Log\LoggerInterface;

/**
 * ОФД Тензор (OFD.ru) - один из крупнейших ОФД в РФ.
 *
 * Реализует интеграцию с API OFD.ru для фискализации чеков по 54-ФЗ.
 */
final readonly class TensorOFDService implements OFDInterface
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
        private readonly HttpClientFactory $http,
        private readonly string $inn,
        private readonly string $apiToken,
        private readonly string $kktRegNumber,
    ) {}

    public function sendReceipt(array $receiptData, string $correlationId): array
    {
        $this->logger->info('Tensor OFD: sending receipt', [
            'inn' => $this->inn,
            'kkt_reg_number' => $this->kktRegNumber,
            'correlation_id' => $correlationId,
        ]);

        try {
            $response = $this->client()->post('/api/v1/receipt', [
                'Inn' => $this->inn,
                'KktRegId' => $this->kktRegNumber,
                'Receipt' => $this->formatReceipt($receiptData),
            ]);

            $data = $response->json();

            $this->audit->log(
                action: 'ofd_receipt_sent',
                subjectType: self::class,
                subjectId: null,
                newValues: [
                    'provider' => 'tensor',
                    'fiscal_sign' => $data['FiscalSign'] ?? null,
                    'fiscal_document_number' => $data['FiscalDocumentNumber'] ?? null,
                ],
                correlationId: $correlationId,
            );

            return [
                'fiscal_sign' => $data['FiscalSign'] ?? '',
                'fiscal_document_number' => $data['FiscalDocumentNumber'] ?? 0,
                'fiscal_document_attribute' => $data['FiscalDocumentAttribute'] ?? 0,
                'ofd_response' => $data,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Tensor OFD: receipt send failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw new \RuntimeException('Failed to send receipt to Tensor OFD: '.$e->getMessage(), 0, $e);
        }
    }

    public function sendCorrection(array $correctionData, string $correlationId): array
    {
        $this->logger->info('Tensor OFD: sending correction', [
            'correlation_id' => $correlationId,
        ]);

        $response = $this->client()->post('/api/v1/correction', [
            'Inn' => $this->inn,
            'KktRegId' => $this->kktRegNumber,
            'Correction' => $correctionData,
        ]);

        $data = $response->json();

        return [
            'fiscal_sign' => $data['FiscalSign'] ?? '',
            'fiscal_document_number' => $data['FiscalDocumentNumber'] ?? 0,
            'ofd_response' => $data,
        ];
    }

    public function getReceiptStatus(string $fiscalSign, string $correlationId): array
    {
        $response = $this->client()->get("/api/v1/receipt/{$fiscalSign}");

        $data = $response->json();

        return [
            'status' => $data['Status'] ?? 'unknown',
            'received_at' => $data['ReceivedAt'] ?? null,
            'ofd_response' => $data,
        ];
    }

    public function getKKTInfo(): array
    {
        $response = $this->client()->get('/api/v1/kkt/info', [
            'Inn' => $this->inn,
            'KktRegId' => $this->kktRegNumber,
        ]);

        $data = $response->json();

        return [
            'kkt_reg_number' => $data['KktRegNumber'] ?? '',
            'ktt_serial' => $data['KttSerial'] ?? '',
            'fn_number' => $data['FnNumber'] ?? '',
            'ofd_response' => $data,
        ];
    }

    public function getProvider(): string
    {
        return 'tensor';
    }

    private function client(): PendingRequest
    {
        return $this->http->timeout(10)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiToken,
                'Content-Type' => 'application/json',
            ])
            ->baseUrl('https://ofd.ru/api');
    }

    /**
     * Форматирование данных чека для API Тензор.
     */
    private function formatReceipt(array $receiptData): array
    {
        return [
            'Type' => $receiptData['type'] ?? 'sell', // sell, sell_refund, buy, buy_refund
            'TaxationType' => $receiptData['taxation_type'] ?? 'usn_income', // osn, usn_income, usn_income_outcome, patent, envd, esn
            'Items' => array_map(fn ($item) => [
                'Name' => $item['name'],
                'Price' => $item['price'],
                'Quantity' => $item['quantity'],
                'Amount' => $item['amount'],
                'Vat' => $item['vat'] ?? 'none', // none, vat0, vat10, vat18, vat110, vat118, vat20, vat120
                'PaymentType' => $item['payment_type'] ?? 1, // 1=full_prepayment, 2=partial_prepayment, 3=advance, 4=full_payment, 5=partial_payment, 6=credit, 7=credit_payment
                'PaymentAgentType' => $item['payment_agent_type'] ?? 0,
            ], $receiptData['items'] ?? []),
            'Payments' => [
                [
                    'Type' => $receiptData['payment_type'] ?? 1, // 1=cash, 2=electronically, 3=advance, 4=credit, 5=compensation
                    'Amount' => $receiptData['total_amount'],
                ],
            ],
            'CustomerContact' => $receiptData['customer_email'] ?? $receiptData['customer_phone'] ?? null,
        ];
    }
}
