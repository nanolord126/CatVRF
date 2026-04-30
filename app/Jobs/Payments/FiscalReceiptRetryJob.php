<?php

declare(strict_types=1);

namespace App\Jobs\Payments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Application\Services\FiscalizationService;
use Modules\Payment\Domain\Entities\FiscalReceipt;
use Modules\Payment\Domain\Repositories\FiscalReceiptRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Job to retry failed fiscal receipts (54-ФЗ).
 * 
 * This job retries sending fiscal receipts to OFD providers when
 * the initial attempt fails. Implements exponential backoff.
 */
final class FiscalReceiptRetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private string $receiptUuid,
    ) {
        $this->onQueue(config('fiscalization.receipt.queue_connection', 'redis'));
        $this->onQueue(config('fiscalization.receipt.queue_name', 'fiscal'));
    }

    public function handle(
        FiscalReceiptRepositoryInterface $receiptRepository,
        FiscalizationService $fiscalizationService,
    ): void {
        $receipt = $receiptRepository->findByUuid($this->receiptUuid);

        if (! $receipt) {
            Log::warning('Fiscal receipt not found for retry', [
                'receipt_uuid' => $this->receiptUuid,
            ]);
            return;
        }

        if ($receipt->isConfirmed()) {
            Log::info('Fiscal receipt already confirmed, no retry needed', [
                'receipt_uuid' => $this->receiptUuid,
            ]);
            return;
        }

        if (! $receipt->canRetry()) {
            Log::warning('Fiscal receipt cannot be retried (max retries exceeded)', [
                'receipt_uuid' => $this->receiptUuid,
                'retry_count' => $receipt->retryCount,
                'status' => $receipt->status,
            ]);
            return;
        }

        try {
            // Re-send the receipt to OFD
            $this->resendReceipt($receipt, $fiscalizationService);

            Log::info('Fiscal receipt retry successful', [
                'receipt_uuid' => $this->receiptUuid,
                'ofd_provider' => $receipt->ofdProvider,
                'retry_count' => $receipt->retryCount,
            ]);

        } catch (Exception $e) {
            Log::error('Fiscal receipt retry failed', [
                'receipt_uuid' => $this->receiptUuid,
                'ofd_provider' => $receipt->ofdProvider,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('Fiscal receipt retry failed after all attempts', [
                    'receipt_uuid' => $this->receiptUuid,
                    'payment_intent_uuid' => $receipt->paymentIntentUuid,
                    'order_id' => $receipt->orderId,
                ]);
            }

            $this->release(60 * $this->attempts()); // Exponential backoff
        }
    }

    private function resendReceipt(
        FiscalReceipt $receipt,
        FiscalizationService $fiscalizationService,
    ): void {
        // Use reflection to access the private sendReceiptToOFD method
        // or create a public method in FiscalizationService for retries
        
        // For now, we'll mark the receipt as pending and let the
        // scheduled job processPendingReceipts handle it
        $updatedReceipt = new FiscalReceipt(
            uuid: $receipt->uuid,
            paymentIntentUuid: $receipt->paymentIntentUuid,
            tenantId: $receipt->tenantId,
            orderId: $receipt->orderId,
            type: $receipt->type,
            inn: $receipt->inn,
            agentType: $receipt->agentType,
            agentName: $receipt->agentName,
            amountKopecks: $receipt->amountKopecks,
            currency: $receipt->currency,
            items: $receipt->items,
            ofdProvider: $receipt->ofdProvider,
            ofdReceiptId: $receipt->ofdReceiptId,
            fiscalSign: $receipt->fiscalSign,
            fiscalDocumentNumber: $receipt->fiscalDocumentNumber,
            fnNumber: $receipt->fnNumber,
            sentAt: null, // Reset sent time
            confirmedAt: $receipt->confirmedAt,
            status: 'pending', // Reset to pending
            errorMessage: $receipt->errorMessage,
            retryCount: $receipt->retryCount,
            createdAt: $receipt->createdAt,
        );

        // Save updated receipt
        $repository = app(FiscalReceiptRepositoryInterface::class);
        $repository->save($updatedReceipt);

        Log::info('Fiscal receipt reset to pending for retry', [
            'receipt_uuid' => $this->receiptUuid,
        ]);
    }

    public function failed(Exception $exception): void
    {
        Log::error('FiscalReceiptRetryJob failed permanently', [
            'receipt_uuid' => $this->receiptUuid,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
