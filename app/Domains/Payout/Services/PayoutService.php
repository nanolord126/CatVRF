<?php declare(strict_types=1);

namespace App\Domains\Payout\Services;

use App\Domains\Payout\DTOs\CreatePayoutRequestDto;
use App\Domains\Payout\Models\PayoutRequest;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final readonly class PayoutService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
        private readonly Request $request,
    ) {}

    /**
     * Create payout request
     */
    public function createRequest(CreatePayoutRequestDto $dto, string $correlationId): PayoutRequest
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check([
            'operation_type' => 'payout_request_create',
            'amount' => $dto->amount,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $request = PayoutRequest::create([
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'amount' => $dto->amount,
                'status' => 'pending',
                'bank_details' => $dto->bankDetails,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'payout_request_created',
                subjectType: PayoutRequest::class,
                subjectId: $request->id,
                newValues: [
                    'tenant_id' => $dto->tenantId,
                    'business_group_id' => $dto->businessGroupId,
                    'amount' => $dto->amount,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Payout request created', [
                'payout_id' => $request->id,
                'amount' => $dto->amount,
                'correlation_id' => $correlationId,
            ]);

            return $request;
        });
    }

    /**
     * Process payout
     */
    public function process(int $payoutRequestId, string $correlationId): void
    {
        $correlationId ??= Str::uuid()->toString();
        $request = PayoutRequest::findOrFail($payoutRequestId);

        if (!$request->isPending()) {
            throw new \DomainException("Payout already processed: {$request->status}");
        }

        $this->fraud->check([
            'operation_type' => 'payout_process',
            'amount' => $request->amount,
            'correlation_id' => $correlationId,
        ]);

        $this->db->transaction(function () use ($request, $correlationId) {
            $request->update([
                'status' => 'processing',
            ]);

            // Integrate with PaymentGatewayService for actual payout
            // This would call the payment gateway to transfer funds

            $this->audit->record(
                action: 'payout_processed',
                subjectType: PayoutRequest::class,
                subjectId: $request->id,
                newValues: ['status' => 'processing'],
                correlationId: $correlationId,
            );

            $this->logger->info('Payout processed', [
                'payout_id' => $request->id,
                'amount' => $request->amount,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Process batch payouts
     */
    public function processBatch(array $payoutRequestIds, string $correlationId): array
    {
        $correlationId ??= Str::uuid()->toString();
        $batchId = Str::uuid()->toString();

        $totalAmount = PayoutRequest::whereIn('id', $payoutRequestIds)
            ->pending()
            ->sum('amount');

        $this->fraud->check([
            'operation_type' => 'payout_batch_process',
            'amount' => (int) $totalAmount,
            'correlation_id' => $correlationId,
        ]);

        $successful = [];
        $failed = [];

        foreach ($payoutRequestIds as $requestId) {
            try {
                $this->process($requestId, $correlationId);
                $successful[] = ['payout_id' => $requestId];
                usleep(100000); // 100ms delay
            } catch (\Exception $e) {
                $failed[] = [
                    'payout_id' => $requestId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'batch_id' => $batchId,
        ];
    }

    /**
     * Cancel payout request
     */
    public function cancel(int $payoutRequestId, string $reason, string $correlationId): void
    {
        $correlationId ??= Str::uuid()->toString();
        $request = PayoutRequest::findOrFail($payoutRequestId);

        if (!$request->canBeCancelled()) {
            throw new \DomainException("Cannot cancel payout with status: {$request->status}");
        }

        $this->fraud->check([
            'operation_type' => 'payout_cancel',
            'correlation_id' => $correlationId,
        ]);

        $this->db->transaction(function () use ($request, $reason, $correlationId) {
            $request->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            $this->audit->record(
                action: 'payout_cancelled',
                subjectType: PayoutRequest::class,
                subjectId: $request->id,
                newValues: ['reason' => $reason],
                correlationId: $correlationId,
            );

            $this->logger->info('Payout cancelled', [
                'payout_id' => $request->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Get payout status
     */
    public function getStatus(int $payoutRequestId): array
    {
        $request = PayoutRequest::findOrFail($payoutRequestId);

        return [
            'payout_id' => $request->id,
            'status' => $request->status,
            'amount' => $request->amount,
            'created_at' => $request->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get payout history
     */
    public function getHistory(int $tenantId, int $perPage = 20)
    {
        return PayoutRequest::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
