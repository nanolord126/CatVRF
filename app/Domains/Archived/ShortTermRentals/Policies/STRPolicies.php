<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PropertyPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;


        public function __construct(


            private readonly FraudControlService $fraudService,


        ) {}


        /**


         * Может ли пользователь просматривать квартиру


         */


        public function view(User $user, Property $property): bool


        {


            // Все могут смотреть активные квартиры


            return $property->is_active;


        }


        /**


         * Может ли пользователь редактировать квартиру (только владелец)


         */


        public function update(User $user, Property $property): bool


        {


            $canUpdate = $user->id === $property->owner_id || $user->isTenantAdmin();


            Log::channel('audit')->info('PropertyPolicy::update checked', [


                'user_id' => $user->id,


                'property_id' => $property->id,


                'can_update' => $canUpdate,


            ]);


            return $canUpdate;


        }


        /**


         * Может ли пользователь удалить квартиру


         */


        public function delete(User $user, Property $property): bool


        {


            $canDelete = $user->id === $property->owner_id || $user->isTenantAdmin();


            Log::channel('audit')->info('PropertyPolicy::delete checked', [


                'user_id' => $user->id,


                'property_id' => $property->id,


                'can_delete' => $canDelete,


            ]);


            return $canDelete;


        }


        /**


         * Может ли пользователь создавать квартиру (максимум 10 за раз)


         */


        public function create(User $user): bool


        {


            // Максимум 10 активных квартир на одного владельца


            $activeCount = Property::where('owner_id', $user->id)


                ->where('is_active', true)


                ->count();


            $canCreate = $activeCount < 10;


            if (!$canCreate) {


                Log::channel('fraud_alert')->warning('User property creation limit exceeded', [


                    'user_id' => $user->id,


                    'active_properties' => $activeCount,


                ]);


            }


            return $canCreate;


        }


    }


    final class BookingPolicy


    {


        use HandlesAuthorization;


        public function __construct(


            private readonly FraudControlService $fraudService,


        ) {}


        /**


         * Может ли пользователь просматривать бронирование


         */


        public function view(User $user, PropertyBooking $booking): bool


        {


            // Может смотреть: гость или владелец квартиры


            $canView = $user->id === $booking->user_id ||


                       $user->id === $booking->property->owner_id ||


                       $user->isTenantAdmin();


            Log::channel('audit')->info('BookingPolicy::view checked', [


                'user_id' => $user->id,


                'booking_id' => $booking->id,


                'can_view' => $canView,


                'correlation_id' => $booking->correlation_id,


            ]);


            return $canView;


        }


        /**


         * Может ли пользователь отменить бронирование


         */


        public function cancel(User $user, PropertyBooking $booking): bool


        {


            // Гость может отменить своё бронирование (если не начале чекин)


            $isGuest = $user->id === $booking->user_id;


            $canCancel = $isGuest && $booking->check_in_date->isFuture();


            // Фрод-защита: частые отмены


            if ($canCancel) {


                $cancellationCount = PropertyBooking::where('user_id', $user->id)


                    ->where('status', 'cancelled')


                    ->where('created_at', '>=', now()->subDays(7))


                    ->count();


                if ($cancellationCount > 10) {


                    Log::channel('fraud_alert')->warning('User booking cancellation limit exceeded', [


                        'user_id' => $user->id,


                        'cancellations_7d' => $cancellationCount,


                        'correlation_id' => $booking->correlation_id,


                    ]);


                    return false;


                }


            }


            Log::channel('audit')->info('BookingPolicy::cancel checked', [


                'user_id' => $user->id,


                'booking_id' => $booking->id,


                'can_cancel' => $canCancel,


                'correlation_id' => $booking->correlation_id,


            ]);


            return $canCancel;


        }


        /**


         * Может ли пользователь создавать бронирование (rate limit)


         */


        public function create(User $user): bool


        {


            // Rate limit: максимум 5 бронирований в день


            $bookingCount = PropertyBooking::where('user_id', $user->id)


                ->where('created_at', '>=', now()->subDay())


                ->count();


            $canCreate = $bookingCount < 5;


            if (!$canCreate) {


                Log::channel('fraud_alert')->warning('User booking creation rate limited', [


                    'user_id' => $user->id,


                    'bookings_24h' => $bookingCount,


                ]);


            }


            return $canCreate;


        }


        /**


         * Может ли владелец подтвердить/отклонить бронирование


         */


        public function approveOrReject(User $user, PropertyBooking $booking): bool


        {


            $isPropertyOwner = $user->id === $booking->property->owner_id;


            $canApprove = $isPropertyOwner && $booking->status === 'pending_verification';


            Log::channel('audit')->info('BookingPolicy::approveOrReject checked', [


                'user_id' => $user->id,


                'booking_id' => $booking->id,


                'can_approve' => $canApprove,


                'correlation_id' => $booking->correlation_id,


            ]);


            return $canApprove;


        }
}
