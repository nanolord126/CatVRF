<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TransportationService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,) {}

        public function bookTransportation(
            TravelTransportation $transportation,
            int $seatsRequired = 1,
            string $correlationId = null,
        ): TravelTransportation {


            $correlationId ??= Str::uuid()->toString();

            try {
                $this->fraudControlService->check(
                    auth()->id() ?? 0,
                    __CLASS__ . '::' . __FUNCTION__,
                    0,
                    request()->ip(),
                    null,
                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
                );
    DB::transaction(function () use (
                    $transportation,
                    $seatsRequired,
                    $correlationId,
                ) {
                    $transportation->lockForUpdate();

                    if ($transportation->available_count < $seatsRequired) {
                        throw new \RuntimeException('Not enough available spaces for transportation');
                    }

                    $transportation->decrement('available_count', $seatsRequired);

                    Log::channel('audit')->info('Transportation booked', [
                        'transportation_id' => $transportation->id,
                        'type' => $transportation->type,
                        'seats_booked' => $seatsRequired,
                        'remaining_spaces' => $transportation->available_count,
                        'commission_amount' => $transportation->commission_amount,
                        'correlation_id' => $correlationId,
                        'timestamp' => now(),
                    ]);

                    TransportationBooked::dispatch($transportation, $correlationId);

                    return $transportation->refresh();
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Transportation booking failed', [
                    'transportation_id' => $transportation->id,
                    'seats_required' => $seatsRequired,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        public function releaseTransportation(
            TravelTransportation $transportation,
            int $seatsToRelease = 1,
            string $correlationId = null,
        ): TravelTransportation {


            $correlationId ??= $transportation->correlation_id ?? Str::uuid()->toString();

            try {
                $this->fraudControlService->check(
                    auth()->id() ?? 0,
                    __CLASS__ . '::' . __FUNCTION__,
                    0,
                    request()->ip(),
                    null,
                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
                );
    DB::transaction(function () use (
                    $transportation,
                    $seatsToRelease,
                    $correlationId,
                ) {
                    $transportation->lockForUpdate();

                    $newAvailable = $transportation->available_count + $seatsToRelease;

                    if ($newAvailable > $transportation->capacity) {
                        throw new \RuntimeException('Cannot release more spaces than transportation capacity');
                    }

                    $transportation->increment('available_count', $seatsToRelease);

                    Log::channel('audit')->info('Transportation spaces released', [
                        'transportation_id' => $transportation->id,
                        'type' => $transportation->type,
                        'spaces_released' => $seatsToRelease,
                        'available_spaces' => $transportation->available_count,
                        'correlation_id' => $correlationId,
                        'timestamp' => now(),
                    ]);

                    return $transportation->refresh();
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Transportation space release failed', [
                    'transportation_id' => $transportation->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
