<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlightService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,) {}

        public function bookFlight(
            TravelFlight $flight,
            string $correlationId = null,
        ): TravelFlight {


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
    DB::transaction(function () use ($flight, $correlationId) {
                    $flight->lockForUpdate();

                    if ($flight->available_seats <= 0) {
                        throw new \RuntimeException('No available seats on this flight');
                    }

                    $flight->decrement('available_seats');

                    Log::channel('audit')->info('Flight booked', [
                        'flight_id' => $flight->id,
                        'flight_number' => $flight->flight_number,
                        'remaining_seats' => $flight->available_seats,
                        'commission_amount' => $flight->commission_amount,
                        'correlation_id' => $correlationId,
                        'timestamp' => now(),
                    ]);

                    FlightBooked::dispatch($flight, $correlationId);

                    return $flight->refresh();
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Flight booking failed', [
                    'flight_id' => $flight->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        public function releaseFlight(
            TravelFlight $flight,
            string $correlationId = null,
        ): TravelFlight {


            $correlationId ??= $flight->correlation_id ?? Str::uuid()->toString();

            try {
                $this->fraudControlService->check(
                    auth()->id() ?? 0,
                    __CLASS__ . '::' . __FUNCTION__,
                    0,
                    request()->ip(),
                    null,
                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
                );
    DB::transaction(function () use ($flight, $correlationId) {
                    $flight->lockForUpdate();

                    $flight->increment('available_seats');

                    Log::channel('audit')->info('Flight seat released', [
                        'flight_id' => $flight->id,
                        'flight_number' => $flight->flight_number,
                        'available_seats' => $flight->available_seats,
                        'correlation_id' => $correlationId,
                        'timestamp' => now(),
                    ]);

                    return $flight->refresh();
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('Flight seat release failed', [
                    'flight_id' => $flight->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
