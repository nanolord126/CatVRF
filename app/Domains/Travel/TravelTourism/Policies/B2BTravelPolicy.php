<?php declare(strict_types=1);

namespace App\Domains\Travel\TravelTourism\Policies;

final class B2BTravelPolicy
{

    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function viewStorefront(User $user, B2BTravelStorefront $storefront): Response
        {
            return $user->tenant_id === $storefront->tenant_id || $user->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет доступа');
        }

        public function createStorefront(User $user): Response
        {
            return $user->tenant_id && $user->has_verified_company
                ? $this->response->allow()
                : $this->response->deny('Требуется верификация');
        }

        public function updateStorefront(User $user, B2BTravelStorefront $storefront): Response
        {
            return $user->tenant_id === $storefront->tenant_id || $user->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет доступа');
        }

        public function viewOrder(User $user, B2BTravelOrder $order): Response
        {
            return $user->tenant_id === $order->tenant_id || $user->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет доступа');
        }

        public function approveOrder(User $user, B2BTravelOrder $order): Response
        {
            return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending'
                ? $this->response->allow()
                : $this->response->deny('Одобрение невозможно');
        }

        public function rejectOrder(User $user, B2BTravelOrder $order): Response
        {
            return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending'
                ? $this->response->allow()
                : $this->response->deny('Отклонение невозможно');
        }

        public function verifyInn(User $user): Response
        {
            return $user->is_admin
                ? $this->response->allow()
                : $this->response->deny('Только администратор');
        }
}
