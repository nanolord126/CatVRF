<?php

declare(strict_types=1);

namespace App\Domains\Education\Kids\Services;

use App\Domains\Education\Kids\DTOs\KidsVoucherCreateDto;
use App\Domains\Education\Kids\Models\KidsVoucher;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KidsVoucherService - Gift and Loyalty Redemptions in BabyAndKids vertical.
 * Layer: Domain Services (3/9)
 */
final readonly class KidsVoucherService
{
    /**
     * Create a new gift voucher.
     */
    public function createVoucher(KidsVoucherCreateDto $dto): KidsVoucher
    {
        Log::channel('audit')->info('Attempting to create kids voucher', [
            'type' => $dto->voucher_type,
            'correlation_id' => $dto->correlation_id
        ]);

        // Fraud control check before mutation
        FraudControlService::check('kids_voucher_create', [
            'type' => $dto->voucher_type,
            'value' => $dto->face_value
        ]);

        return DB::transaction(function () use ($dto) {
            $voucher = KidsVoucher::create($dto->toArray());

            Log::channel('audit')->info('Kids voucher created successfully', [
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
        DB::transaction(function () use ($code, $amount, $correlationId) {
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

            Log::channel('audit')->info('Voucher balance redeemed', [
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
        DB::transaction(function () use ($voucherId, $amount, $correlationId) {
            $voucher = KidsVoucher::where('id', $voucherId)->lockForUpdate()->firstOrFail();

            if (!$voucher->is_rechargeable) {
                throw new \RuntimeException("Voucher ID: {$voucherId} is not rechargeable.");
            }

            $voucher->increment('current_balance', $amount);
            $voucher->update(['status' => 'active']);

            Log::channel('audit')->info('Voucher balance recharged', [
                'voucher_id' => $voucherId,
                'amount' => $amount,
                'correlation_id' => $correlationId
            ]);
        });
    }
}
