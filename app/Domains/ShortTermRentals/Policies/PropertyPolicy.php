<?php

declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Policies;

use HandlesAuthorization;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

final class PropertyPolicy
{

    use HandlesAuthorization;

        public function __construct(
            private readonly FraudControlService $fraudService, private readonly Request $request, private readonly LoggerInterface $logger) {}

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

            $this->logger->info('PropertyPolicy::update checked', [
                'user_id' => $user->id,
                'property_id' => $property->id,
                'can_update' => $canUpdate,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            return $canUpdate;
        }

        /**
         * Может ли пользователь удалить квартиру
         */
        public function delete(User $user, Property $property): bool
        {
            $canDelete = $user->id === $property->owner_id || $user->isTenantAdmin();

            $this->logger->info('PropertyPolicy::delete checked', [
                'user_id' => $user->id,
                'property_id' => $property->id,
                'can_delete' => $canDelete,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
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
                $this->logger->warning('User property creation limit exceeded', [
                    'user_id' => $user->id,
                    'active_properties' => $activeCount,
                ]);
            }

            return $canCreate;
        }
    }
