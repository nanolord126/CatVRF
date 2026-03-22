<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;

use App\Services\{FraudControlService, WalletService, PaymentService};
use App\Domains\ShortTermRentals\Models\{Apartment, ApartmentBooking};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Str;

final class ApartmentService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly PaymentService $payment,
    ) {}

    public function createBooking(array $data, bool $isB2B): array
    {
        $cid = Str::uuid()->toString();
        Log::channel('audit')->info('Apartment booking', compact('cid', 'isB2B'));
        $this->fraud->check(0, 'apartment_booking', 0, null, null, $cid);

        return DB::transaction(function () use ($data, $isB2B, $cid) {
            $apt = Apartment::findOrFail($data['apartment_id']);
            $nights = now()->parse($data['check_in'])->diffInDays($data['check_out']);
            $price = $apt->price_per_night * $nights;
            $finalPrice = $isB2B ? $price * 0.90 : $price;

            $booking = ApartmentBooking::create([
                'tenant_id' => tenant()->id,
                'apartment_id' => $apt->id,
                'user_id' => $data['user_id'] ?? null,
                'inn' => $data['inn'] ?? null,
                'business_card_id' => $data['business_card_id'] ?? null,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests_count' => $data['guests_count'],
                'total_price' => $finalPrice,
                'deposit_held' => $apt->deposit_amount,
                'status' => 'pending',
                'correlation_id' => $cid,
            ]);

            return ['booking' => $booking, 'correlation_id' => $cid];
        });
    }
}
