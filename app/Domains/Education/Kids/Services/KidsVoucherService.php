<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class KidsVoucherService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Create a new gift voucher.
         */
        public function createVoucher(KidsVoucherCreateDto $dto): KidsVoucher
        {
            $this->logger->info('Attempting to create kids voucher', [
                'type' => $dto->voucher_type,
                'correlation_id' => $dto->correlation_id
            ]);

            // Fraud control check before mutation
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'kids_voucher_create', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($dto) {
                $voucher = KidsVoucher::create($dto->toArray());

                $this->logger->info('Kids voucher created successfully', [
                    'voucher_id' => $voucher->id,
                    'correlation_id' => $dto->correlation_id
                ]);

                return $voucher;
            });
        }

        /**
         * Redeem balance from a voucher.
         */
        public function redeemVoucherValue(string $code, int $amount, string $correlationId): void
        {
            $this->db->transaction(function () use ($code, $amount, $correlationId) {
                $voucher = KidsVoucher::where('code', $code)->lockForUpdate()->firstOrFail();

                if (!$voucher->isValid()) {
                    throw new \RuntimeException("Voucher ID: {$voucher->id} is not valid.");
                }

                if ($voucher->current_balance < $amount) {
                    throw new \RuntimeException("Insufficient balance on voucher code: {$code}");
                }

                $voucher->decrement('current_balance', $amount);

                if ($voucher->current_balance === 0) {
                    $voucher->update(['status' => 'redeemed']);
                }

                $this->logger->info('Voucher balance redeemed', [
                    'voucher_id' => $voucher->id,
                    'amount' => $amount,
                    'correlation_id' => $correlationId
                ]);
            });
        }

        /**
         * Top-up balance for rechargeable vouchers.
         */
        public function rechargeVoucher(int $voucherId, int $amount, string $correlationId): void
        {
            $this->db->transaction(function () use ($voucherId, $amount, $correlationId) {
                $voucher = KidsVoucher::where('id', $voucherId)->lockForUpdate()->firstOrFail();

                if (!$voucher->is_rechargeable) {
                    throw new \RuntimeException("Voucher ID: {$voucherId} is not rechargeable.");
                }

                $voucher->increment('current_balance', $amount);
                $voucher->update(['status' => 'active']);

                $this->logger->info('Voucher balance recharged', [
                    'voucher_id' => $voucherId,
                    'amount' => $amount,
                    'correlation_id' => $correlationId
                ]);
            });
        }
}
