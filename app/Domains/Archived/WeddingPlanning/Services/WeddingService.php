<?php declare(strict_types=1);

namespace App\Domains\Archived\WeddingPlanning\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WeddingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * Конструктор с зависимостями


         */


        public function __construct(


            private FraudControlService $fraudControl,


            private string $correlationId = ''


        ) {


            $this->correlationId = $this->correlationId ?: (string) Str::uuid();


        }


        /**


         * Инициация новой свадьбы (B2C/B2B)


         *


         * @param array $data Входные данные (title, event_date, budget, etc)


         * @return WeddingEvent


         * @throws \Exception


         */


        public function createWedding(array $data): WeddingEvent


        {


            Log::channel('audit')->info('WeddingService: Initiating wedding creation', [


                'correlation_id' => $this->correlationId,


                'data' => $data


            ]);


            // 1. Fraud Check


            $this->fraudControl->check([


                'operation' => 'wedding_create',


                'user_id' => $data['owner_id'] ?? null,


                'budget' => $data['total_budget'] ?? 0


            ]);


            return DB::transaction(function () use ($data) {


                // 2. Создание события


                $event = WeddingEvent::create([


                    'owner_id' => $data['owner_id'],


                    'title' => $data['title'],


                    'event_date' => $data['event_date'],


                    'location' => $data['location'] ?? null,


                    'guest_count' => $data['guest_count'] ?? 0,


                    'total_budget' => $data['total_budget'] ?? 0,


                    'status' => 'planning',


                    'correlation_id' => $this->correlationId,


                    'tags' => $data['tags'] ?? [],


                ]);


                // 3. Создание черновика договора


                $this->createInitialContract($event);


                Log::channel('audit')->info('WeddingService: Wedding created successfully', [


                    'event_uuid' => $event->uuid,


                    'correlation_id' => $this->correlationId


                ]);


                return $event;


            });


        }


        /**


         * Бронирование услуги или пакета


         */


        public function bookService(WeddingEvent $event, $bookable, int $amount, int $prepayment): WeddingBooking


        {


            Log::channel('audit')->info('WeddingService: Booking service', [


                'event_uuid' => $event->uuid,


                'bookable_type' => get_class($bookable),


                'amount' => $amount,


                'correlation_id' => $this->correlationId


            ]);


            return DB::transaction(function () use ($event, $bookable, $amount, $prepayment) {


                // Optimistic Locking на бюджет события (если нужно)


                $event->lockForUpdate();


                $booking = WeddingBooking::create([


                    'event_id' => $event->id,


                    'bookable_type' => get_class($bookable),


                    'bookable_id' => $bookable->id,


                    'amount' => $amount,


                    'prepayment_amount' => $prepayment,


                    'status' => 'pending',


                    'booked_at' => now(),


                    'correlation_id' => $this->correlationId,


                ]);


                return $booking;


            });


        }


        /**


         * Внутренний метод создания договора


         */


        private function createInitialContract(WeddingEvent $event): WeddingContract


        {


            return WeddingContract::create([


                'event_id' => $event->id,


                'contract_number' => 'WD-' . strtoupper(Str::random(8)),


                'terms' => [


                    'prepayment_percent' => 30,


                    'cancellation_policy' => '14_days_full_refund',


                    'rescheduling_fee' => 500000, // 5000 руб


                ],


                'status' => 'draft',


                'correlation_id' => $this->correlationId,


            ]);


        }


        /**


         * Изменение статуса свадьбы с аудитом


         */


        public function updateStatus(WeddingEvent $event, string $newStatus): bool


        {


            Log::channel('audit')->info('WeddingService: Status update', [


                'event_uuid' => $event->uuid,


                'old_status' => $event->status,


                'new_status' => $newStatus,


                'correlation_id' => $this->correlationId


            ]);


            return $event->update([


                'status' => $newStatus,


                'correlation_id' => $this->correlationId


            ]);


        }
}
