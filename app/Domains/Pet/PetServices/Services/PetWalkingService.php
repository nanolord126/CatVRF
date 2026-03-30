<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetWalkingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
            private readonly WalletService $walletService,
        ) {}

        public function createWalkingBooking(array $data): PetWalking
        {
            Log::channel('audit')->info('PetWalkingService: Creating walking booking', [
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'walker_id' => $data['walker_id'],
                'tenant_id' => filament()->getTenant()->id,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(fn () => PetWalking::create([
                'uuid' => Str::uuid(),
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'tenant_id' => filament()->getTenant()->id,
                'pet_id' => $data['pet_id'],
                'walker_id' => $data['walker_id'],
                'walk_date' => $data['walk_date'],
                'walk_time' => $data['walk_time'],
                'duration_minutes' => $data['duration_minutes'] ?? 30,
                'price' => $data['price'] ?? 50000,
                'location' => $data['location'] ?? 'home',
                'status' => 'pending',
                'special_instructions' => $data['special_instructions'] ?? [],
                'tags' => $data['tags'] ?? [],
            ]));
        }

        public function acceptWalkingBooking(int $bookingId, int $walkerId): bool
        {
            $booking = PetWalking::findOrFail($bookingId);

            Log::channel('audit')->info('PetWalkingService: Walker accepted booking', [
                'correlation_id' => $booking->correlation_id,
                'booking_id' => $bookingId,
                'walker_id' => $walkerId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'accepted']);
                return true;
            });
        }

        public function startWalk(int $bookingId): bool
        {
            $booking = PetWalking::findOrFail($bookingId);

            Log::channel('audit')->info('PetWalkingService: Walk started', [
                'correlation_id' => $booking->correlation_id,
                'booking_id' => $bookingId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($booking) {
                $booking->update([
                    'status' => 'in_progress',
                    'start_time' => now(),
                ]);
                return true;
            });
        }

        public function completeWalk(int $bookingId, array $photoUrls = [], string $notes = ''): bool
        {
            $booking = PetWalking::findOrFail($bookingId);

            Log::channel('audit')->info('PetWalkingService: Walk completed', [
                'correlation_id' => $booking->correlation_id,
                'booking_id' => $bookingId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($booking, $photoUrls, $notes) {
                $booking->update([
                    'status' => 'completed',
                    'end_time' => now(),
                    'photo_urls' => $photoUrls,
                    'completion_notes' => $notes,
                ]);

                // Credit to walker wallet
                $this->walletService->credit(
                    tenantId: $booking->tenant_id,
                    amount: (int) ($booking->price * 0.85),
                    reason: 'walking_service_completed',
                    correlationId: $booking->correlation_id,
                );

                return true;
            });
        }

        public function getAvailableWalkers(string $walkDate, string $walkTime): Collection
        {
            return collect()->map(function () {
                return [
                    'walker_id' => random_int(1, 10),
                    'rating' => random_int(45, 50) / 10,
                    'available' => true,
                ];
            })->take(5);
        }

        public function cancelWalkingBooking(int $bookingId, string $reason = ''): bool
        {
            $booking = PetWalking::findOrFail($bookingId);

            Log::channel('audit')->info('PetWalkingService: Cancelling walking booking', [
                'correlation_id' => $booking->correlation_id,
                'booking_id' => $bookingId,
                'reason' => $reason,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($booking, $reason) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                ]);

                return true;
            });
        }
}
