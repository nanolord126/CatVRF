<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\OFD;

use App\Domains\Payment\Contracts\OFDInterface;
use App\Services\AuditService;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Http\Client\PendingRequest;
use Psr\Log\LoggerInterface;

/**
 * ОФД Контур (Kontur) - популярный ОФД в РФ.
 *
 * Реализует интеграцию с API Контур для фискализации чеков по 54-ФЗ.
 */
final readonly class KonturOFDService implements OFDInterface
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
        private readonly HttpClientFactory $http,
        private readonly string $apiKey,
        private readonly string $kktRegNumber,
    ) {}

    public function sendReceipt(array $receiptData, string $correlationId): array
    {
        $this->logger->info('Kontur OFD: sending receipt', [
            'kkt_reg_number' => $this->kktRegNumber,
            'correlation_id' => $correlationId,
        ]);

        try {
            $response = $this->client()->post('/v2/receipt', [
                'kktRegId' => $this->kktRegNumber,
                'receipt' => $this->formatReceipt($receiptData),
            ]);

            $data = $response->json();

            $this->audit->log(
                action: 'ofd_receipt_sent',
                subjectType: self::class,
                subjectId: null,
                newValues: [
                    'provider' => 'kontur',
                    'fiscal_sign' => $data['fiscalSign'] ?? null,
                    'fiscal_document_number' => $data['fiscalDocumentNumber'] ?? null,
                ],
                correlationId: $correlationId,
            );

            return [
                'fiscal_sign' => $data['fiscalSign'] ?? '',
                'fiscal_document_number' => $data['fiscalDocumentNumber'] ?? 0,
                'fiscal_document_attribute' => $data['fiscalDocumentAttribute'] ?? 0,
                'ofd_response' => $data,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Kontur OFD: receipt send failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw new \RuntimeException('Failed to send receipt to Kontur OFD: '.$e->getMessage(), 0, $e);
        }
    }

    public function sendCorrection(array $correctionData, string $correlationId): array
    {
        $this->logger->info('Kontur OFD: sending correction', [
            'correlation_id' => $correlationId,
        ]);

        $response = $this->client()->post('/v2/correction', [
            'kktRegId' => $this->kktRegNumber,
            'correction' => $correctionData,
        ]);

        $data = $response->json();

        return [
            'fiscal_sign' => $data['fiscalSign'] ?? '',
            'fiscal_document_number' => $data['fiscalDocumentNumber'] ?? 0,
            'ofd_response' => $data,
        ];
    }

    public function getReceiptStatus(string $fiscalSign, string $correlationId): array
    {
        $response = $this->client()->get("/v2/receipt/{$fiscalSign}");

        $data = $response->json();

        return [
            'status' => $data['status'] ?? 'unknown',
            'received_at' => $data['receivedAt'] ?? null,
            'ofd_response' => $data,
        ];
    }

    public function getKKTInfo(): array
    {
        $response = $this->client()->get('/v2/kkt/info', [
            'kktRegId' => $this->kktRegNumber,
        ]);

        $data = $response->json();

        return [
            'kkt_reg_number' => $data['kktRegNumber'] ?? '',
            'ktt_serial' => $data['kttSerial'] ?? '',
            'fn_number' => $data['fnNumber'] ?? '',
            'ofd_response' => $data,
        ];
    }

    public function getProvider(): string
    {
        return 'kontur';
    }

    private function client(): PendingRequest
    {
        return $this->http->timeout(10)
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->baseUrl('https://api.kontur-ofd.ru');
    }

    private function formatReceipt(array $receiptData): array
    {
        return [
            'type' => $receiptData['type'] ?? 'sell',
            'taxationType' => $receiptData['taxation_type'] ?? 'usn_income',
            'items' => array_map(fn ($item) => [
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'amount' => $item['amount'],
                'vat' => $item['vat'] ?? 'none',
                'paymentType' => $item['payment_type'] ?? 1,
            ], $receiptData['items'] ?? []),
            'payments' => [
                [
                    'type' => $receiptData['payment_type'] ?? 1,
                    'amount' => $receiptData['total_amount'],
                ],
            ],
            'customerContact' => $receiptData['customer_email'] ?? $receiptData['customer_phone'] ?? null,
        ];
    }
}
