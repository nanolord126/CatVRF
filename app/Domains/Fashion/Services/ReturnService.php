<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ReturnService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function requestReturn(
            int $tenantId,
            int $orderId,
            int $customerId,
            float $returnAmount,
            string $reason,
            ?string $correlationId = null
    ): FashionReturn {

            try {
                $correlationId ??= Str::uuid()->toString();

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $return = $this->db->transaction(function () use (
                    $tenantId,
                    $orderId,
                    $customerId,
                    $returnAmount,
                    $reason,
                    $correlationId
    ) {
                    $return = FashionReturn::create([
                        'uuid' => Str::uuid()->toString(),
                        'tenant_id' => $tenantId,
                        'order_id' => $orderId,
                        'customer_id' => $customerId,
                        'return_number' => 'RET-'.Str::upper(Str::random(8)),
                        'return_amount' => $returnAmount,
                        'reason' => $reason,
                        'status' => 'requested',
                        'requested_at' => Carbon::now(),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Fashion return requested', [
                        'return_id' => $return->id,
                        'order_id' => $orderId,
                        'customer_id' => $customerId,
                        'return_amount' => $returnAmount,
                        'correlation_id' => $correlationId,
                    ]);

                    return $return;
                });

                return $return;
            } catch (Throwable $e) {
                $this->logger->error('Failed to request fashion return', [
                    'error' => $e->getMessage(),
                    'order_id' => $orderId,
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                throw $e;
            }
        }

        public function approveReturn(FashionReturn $return, float $refundAmount, ?string $correlationId = null): void
        {

            try {
                $correlationId ??= Str::uuid()->toString();

                            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($return, $refundAmount, $correlationId) {
                    $return->update([
                        'status' => 'approved',
                        'refund_amount' => $refundAmount,
                        'approved_at' => Carbon::now(),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Fashion return approved', [
                        'return_id' => $return->id,
                        'refund_amount' => $refundAmount,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to approve fashion return', [
                    'return_id' => $return->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                throw $e;
            }
        }

        public function processRefund(FashionReturn $return, ?string $correlationId = null): void
        {

            try {
                $correlationId ??= Str::uuid()->toString();

                            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($return, $correlationId) {
                    $return->update([
                        'status' => 'refunded',
                        'refunded_at' => Carbon::now(),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Fashion return refunded', [
                        'return_id' => $return->id,
                        'refund_amount' => $return->refund_amount,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to process fashion return refund', [
                    'return_id' => $return->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                throw $e;
            }
        }
}
