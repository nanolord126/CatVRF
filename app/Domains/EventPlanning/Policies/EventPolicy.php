<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Policies;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final class EventPolicy
{
    public function __construct(
        private readonly LoggerInterface $logger) {}
        /**
         * Просмотр списка событий (List/View)
         */
        public function viewAny(User $user): bool
        {
            $this->logger->info('Security: UI Access - ViewAny Events', [
                'user_id' => $user->id,
                'tenant_id' => tenant()->id,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            return $user->hasRole(['admin', 'business_owner', 'event_planner']);
        }

        /**
         * Просмотр конкретного события (Detail)
         */
        public function view(User $user, Event $event): bool
        {
            return $user->tenant_id === $event->tenant_id;
        }

        /**
         * Создание события (Create)
         */
        public function create(User $user): bool
        {
            $this->logger->info('Security: UI Access - Create Event', [
                'user_id' => $user->id,
                'tenant_id' => tenant()->id,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            return $user->hasRole(['admin', 'business_owner', 'event_planner']);
        }

        /**
         * Редактирование события (Edit)
         */
        public function update(User $user, Event $event): bool
        {
            if ($user->tenant_id !== $event->tenant_id) {
                $this->logger->warning('Security: Cross-tenant edit attempt', [
                    'user_id' => $user->id,
                    'event_uuid' => $event->uuid,
                    'event_tenant' => $event->tenant_id,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return false;
            }

            return $user->hasRole(['admin', 'business_owner', 'event_planner']);
        }

        /**
         * Удаление события (Delete)
         */
        public function delete(User $user, Event $event): bool
        {
            if ($user->tenant_id !== $event->tenant_id) {
                return false;
            }

            // Удаление разрешено только админу или владельцу
            return $user->hasRole(['admin', 'business_owner']);
        }

        /**
         * Отмена события (Special Action)
         */
        public function cancel(User $user, Event $event): bool
        {
            return $this->update($user, $event);
        }
}
