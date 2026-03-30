<?php declare(strict_types=1);

namespace App\Domains\Archived\Photography\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PhotographyB2BService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * Массовое бронирование для бизнеса (Корпоративные съемки)


         */


        public function createBatchCorporateBooking(


            int $tenantId,


            int $sessionId,


            array $timeSlots, // Array of datetimes


            ?string $correlationId = null


        ): array {


            $correlationId ??= (string) \Illuminate\Support\Str::uuid();


            $results = [];


            return DB::transaction(function () use ($tenantId, $sessionId, $timeSlots, $correlationId, &$results) {


                Log::channel('audit')->info('B2B Corporate Batch Session Booking Triggered', [


                    'tenant_id' => $tenantId,


                    'slots_count' => count($timeSlots),


                    'correlation_id' => $correlationId


                ]);


                foreach ($timeSlots as $slot) {


                    // Создание отдельных записей для каждого слота


                    $booking = Booking::create([


                        'uuid' => (string) \Illuminate\Support\Str::uuid(),


                        'client_id' => 0, // System B2B marker


                        'session_id' => $sessionId,


                        'starts_at' => $slot['start'],


                        'ends_at' => $slot['end'],


                        'status' => 'confirmed',


                        'total_amount_kopecks' => 0, // B2B contract pricing


                        'correlation_id' => $correlationId,


                        'tags' => ['b2b', 'corporate', 'batch']


                    ]);


                    $results[] = $booking->uuid;


                }


                Log::channel('audit')->info('B2B Corporate Batch Complete', [


                    'count' => count($results),


                    'correlation_id' => $correlationId


                ]);


                return $results;


            });


        }
}
