<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\External\OFD;

use Illuminate\Http\Client\Factory as HttpClient;
use Modules\Payment\Domain\Contracts\OFDInterface;
use Psr\Log\LoggerInterface;

// TODO: Implement Tensor OFD with full API integration
final class TensorOFDService implements OFDInterface
{
    private const API_URL = 'https://ofd.ru/api';

    public function __construct(
        private readonly HttpClient $http,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
    ) {}

    public function sendReceipt(array $receiptData, string $correlationId): array
    {
        // TODO: Implement Tensor API call
        $this->logger->info('Tensor OFD: sendReceipt', ['correlation_id' => $correlationId]);
        return [
            'fiscal_sign' => '',
            'fiscal_document_number' => 0,
            'fiscal_document_attribute' => 0,
            'ofd_response' => [],
        ];
    }

    public function sendCorrection(array $correctionData, string $correlationId): array
    {
        // TODO: Implement Tensor correction
        $this->logger->info('Tensor OFD: sendCorrection', ['correlation_id' => $correlationId]);
        return [
            'fiscal_sign' => '',
            'fiscal_document_number' => 0,
            'ofd_response' => [],
        ];
    }

    public function getReceiptStatus(string $fiscalSign, string $correlationId): array
    {
        // TODO: Implement Tensor status check
        $this->logger->info('Tensor OFD: getReceiptStatus', ['correlation_id' => $correlationId]);
        return [
            'status' => '',
            'received_at' => null,
            'ofd_response' => [],
        ];
    }

    public function getKKTInfo(): array
    {
        // TODO: Implement Tensor KKT info
        return [
            'kkt_reg_number' => '',
            'ktt_serial' => '',
            'fn_number' => '',
            'ofd_response' => [],
        ];
    }

    public function getProvider(): string
    {
        return 'tensor';
    }
}
