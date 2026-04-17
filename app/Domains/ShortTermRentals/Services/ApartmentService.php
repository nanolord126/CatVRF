<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\ShortTermRentals\Models\{Apartment, ApartmentBooking};
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Services\PaymentService;
use App\Domains\Payment\Services\PaymentServiceAdapter;
use Illuminate\Support\Str;

/**
 * Class ApartmentService
 *
 * Part of the ShortTermRentals vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\ShortTermRentals\Services
 */
final readonly class ApartmentService
{
    public function __construct(private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly PaymentServiceAdapter $payment,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    public function createBooking(array $data, bool $isB2B): array
    {
        $cid = Str::uuid()->toString();
        $this->logger->info('Apartment booking', ['correlation_id' => $cid, 'is_b2b' => $isB2B]);
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'apartment_booking', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($data, $isB2B, $cid) {
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
