<?php declare(strict_types=1);

namespace App\Services\Cleaning;




use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Models\Cleaning\CleaningOrder;
use App\Models\Cleaning\CleaningAddress;
use App\Models\Cleaning\CleaningService as ServiceModel;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class CleaningBookingService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService $fraud,
        private WalletService $wallet,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

        /**
         * Creates a new cleaning order with full audit logging and secure hold.
         *
         * @param array $data Input data from the request.
         * @return CleaningOrder Created order.
         * @throws \Exception If fraud check fails or stock is unavailable.
         */
        public function createOrder(array $data): CleaningOrder
        {
            $service = ServiceModel::findOrFail($data['cleaning_service_id']);

            // 1. Mandatory Fraud Check before any mutation
            $fraudResult = $this->fraud->check(
                (int) ($data['user_id'] ?? 0),
                'cleaning_order_create',
                (int) ($service->price_base_cents ?? 0),
                (string) $this->request->ip(),
                null,
                $this->correlationId(),
            );

            if (($fraudResult['decision'] ?? 'allow') === 'review') {
                $this->logger->channel('audit')->error('Fraud check failed for cleaning booking', [
                    'correlation_id' => $this->correlationId(),
                    'data' => $data,
                ]);
                throw new \RuntimeException('Operation requires manual fraud review.');
            }

            return $this->db->transaction(function () use ($data, $service) {
                $address = CleaningAddress::findOrFail($data['cleaning_address_id']);
                $company = $service->company;

                // 2. Pricing calculation (Base + Area/Duration multiplier)
                $totalCents = $this->calculateTotal($service, $address);

                // 3. Order instantiation with 2026 Canonical metadata
                $order = CleaningOrder::create([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => (int) $data['user_id'],
                    'cleaning_company_id' => $company->id,
                    'cleaning_service_id' => $service->id,
                    'cleaning_address_id' => $address->id,
                    'status' => 'pending',
                    'scheduled_at' => $data['scheduled_at'],
                    'total_cents' => $totalCents,
                    'prepayment_cents' => (int) ($totalCents * 0.3), // 30% prepayment
                    'client_wishes' => $data['client_wishes'] ?? null,
                    'correlation_id' => $this->correlationId(),
                ]);

                // 4. Secure Hold via WalletService (only for B2C, B2B has contracts)
                if (!$company->isB2B()) {
                    $this->wallet->holdAmount($order->user_id, $order->prepayment_cents, 'Cleaning Booking Prepayment', $this->correlationId());
                }

                // 5. Mandatory Audit Log
                $this->logger->channel('audit')->info('Cleaning Order Created', [
                    'order_uuid' => $order->uuid,
                    'user_id' => $order->user_id,
                    'total_cents' => $order->total_cents,
                    'correlation_id' => $this->correlationId(),
                ]);

                return $order;
            });
        }

        /**
         * Start the cleaning job (Photofix Before mandatory).
         */
        public function startJob(int $orderId, array $photosBefore): CleaningOrder
        {
            return $this->db->transaction(function () use ($orderId, $photosBefore) {
                $order = CleaningOrder::where('id', $orderId)->lockForUpdate()->firstOrFail();

                if ($order->status !== 'pending' && $order->status !== 'confirmed') {
                    throw new \LogicException('Job cannot be started in current status.');
                }

                $order->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'photos_before' => $photosBefore,
                ]);

                $this->logger->channel('audit')->info('Cleaning Job Started', [
                    'order_id' => $orderId,
                    'photos_count' => count($photosBefore),
                    'correlation_id' => $this->correlationId(),
                ]);

                return $order;
            });
        }

        /**
         * Quality Inspection and Completion.
         */
        public function completeJob(int $orderId, array $photosAfter, array $qaResult): CleaningOrder
        {
            return $this->db->transaction(function () use ($orderId, $photosAfter, $qaResult) {
                $order = CleaningOrder::where('id', $orderId)->lockForUpdate()->firstOrFail();

                if ($order->status !== 'in_progress') {
                    throw new \LogicException('Only in-progress jobs can be completed.');
                }

                $order->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                    'photos_after' => $photosAfter,
                    'inspection_data' => $qaResult,
                ]);

                // Final settlement
                $this->wallet->debit($order->user_id, $order->prepayment_cents, \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $this, null, null, [
                    'order_id' => $orderId,
                    'correlation_id' => $this->correlationId(),
                ]);

                return $order;
            });
        }

        /**
         * Calculation logic for cleaning prices.
         */
        private function calculateTotal(ServiceModel $service, CleaningAddress $address): int
        {
            $base = $service->price_base_cents;

            // Multiplier based on area SQM
            $multiplier = $address->area_sqm > 50 ? ($address->area_sqm / 50) : 1;

            // Residential vs Commercial adjustment
            $typeAdjustment = $address->isCommercial() ? 1.4 : 1.0;

            return (int) ($base * $multiplier * $typeAdjustment);
        }
}
