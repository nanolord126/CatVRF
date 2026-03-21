<?php declare(strict_types=1);

namespace App\Domains\PetServices\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\PetServices\Models\PetWalking;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PetWalkingService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function createWalkingBooking(array $data): PetWalking
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createWalkingBooking', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createWalkingBooking', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createWalkingBooking', ['domain' => __CLASS__]);

        Log::channel('audit')->info('PetWalkingService: Creating walking booking', [
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'walker_id' => $data['walker_id'],
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return DB::transaction(fn () => PetWalking::create([
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'acceptWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL acceptWalkingBooking', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'acceptWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL acceptWalkingBooking', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'acceptWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL acceptWalkingBooking', ['domain' => __CLASS__]);

        $booking = PetWalking::findOrFail($bookingId);

        Log::channel('audit')->info('PetWalkingService: Walker accepted booking', [
            'correlation_id' => $booking->correlation_id,
            'booking_id' => $bookingId,
            'walker_id' => $walkerId,
        ]);

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'accepted']);
            return true;
        });
    }

    public function startWalk(int $bookingId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'startWalk'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL startWalk', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'startWalk'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL startWalk', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'startWalk'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL startWalk', ['domain' => __CLASS__]);

        $booking = PetWalking::findOrFail($bookingId);

        Log::channel('audit')->info('PetWalkingService: Walk started', [
            'correlation_id' => $booking->correlation_id,
            'booking_id' => $bookingId,
        ]);

        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'in_progress',
                'start_time' => now(),
            ]);
            return true;
        });
    }

    public function completeWalk(int $bookingId, array $photoUrls = [], string $notes = ''): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'completeWalk'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeWalk', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'completeWalk'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeWalk', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'completeWalk'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeWalk', ['domain' => __CLASS__]);

        $booking = PetWalking::findOrFail($bookingId);

        Log::channel('audit')->info('PetWalkingService: Walk completed', [
            'correlation_id' => $booking->correlation_id,
            'booking_id' => $bookingId,
        ]);

        return DB::transaction(function () use ($booking, $photoUrls, $notes) {
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableWalkers'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableWalkers', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableWalkers'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableWalkers', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableWalkers'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableWalkers', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelWalkingBooking', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelWalkingBooking', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelWalkingBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelWalkingBooking', ['domain' => __CLASS__]);

        $booking = PetWalking::findOrFail($bookingId);

        Log::channel('audit')->info('PetWalkingService: Cancelling walking booking', [
            'correlation_id' => $booking->correlation_id,
            'booking_id' => $bookingId,
            'reason' => $reason,
        ]);

        return DB::transaction(function () use ($booking, $reason) {
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            return true;
        });
    }
}
