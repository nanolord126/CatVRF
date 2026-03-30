<?php declare(strict_types=1);

namespace App\Domains\Archived\MusicAndInstruments\Music\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleRentalExpirationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


        /**


         * Create a new job instance.


         */


        public function __construct(


            public int $bookingId,


            public string $correlationId


        ) {}


        /**


         * Execute the job.


         */


        public function handle(): void


        {


            DB::transaction(function () {


                $booking = MusicBooking::lockForUpdate()


                    ->where('bookable_type', MusicInstrument::class)


                    ->where('status', 'confirmed')


                    ->find($this->bookingId);


                if (!$booking || $booking->ends_at > now()) {


                    return;


                }


                $instrument = MusicInstrument::lockForUpdate()->find($booking->bookable_id);


                if ($instrument && $instrument->hold_stock > 0) {


                    $instrument->decrement('hold_stock');


                    $booking->update(['status' => 'completed']);


                    Log::channel('audit')->info('Expired rental auto-completed', [


                        'booking_id' => $this->bookingId,


                        'instrument_id' => $instrument->id,


                        'correlation_id' => $this->correlationId,


                    ]);


                }


            });


        }


        /**


         * Get tags for the job.


         */


        public function tags(): array


        {


            return ['music', 'rental', 'expiration', 'booking:' . $this->bookingId];


        }
}
