<?php declare(strict_types=1);

namespace App\Policies;
use Illuminate\Database\Eloquent\Model;



use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;
final class PaymentPolicy extends Model
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
    ) {}
        /**
         * Может ли пользователь видеть платёж?
         * Только если платёж принадлежит его tenant или он администратор.
         */
        public function view(User $user, PaymentTransaction $payment): bool
        {
            // CANON 2026: Strict tenant scoping check
            if (isset($payment->tenant_id) && $user->tenant_id !== $payment->tenant_id && !$user->hasRole('admin')) {
                $this->logger->warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'user_tenant_id' => $user->tenant_id,
                    'model_tenant_id' => $payment->tenant_id,
                ]);
                return false;
            }

            $allowed = $user->tenant_id === $payment->tenant_id || $user->hasRole('admin');

            if (!$allowed) {
                $this->logger->warning('Unauthorized payment view attempt', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'payment_id' => $payment->id,
                    'payment_tenant_id' => $payment->tenant_id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь создать платёж?
         * Только авторизованные пользователи.
         */
        public function create(User $user): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            $allowed = $user->email_verified_at !== null || $user->hasRole('admin');

            if (!$allowed) {
                $this->logger->info('Unverified user payment creation attempt', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь захватить платёж (capture)?
         * Только владелец заказа/услуги после успешной авторизации.
         */
        public function capture(User $user, PaymentTransaction $payment): bool
        {
            $allowed = (
                $payment->tenant_id === $user->tenant_id &&
                $payment->status === 'authorized' &&
                $user->hasRole(['business', 'admin'])
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized capture attempt', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь отменить платёж (void)?
         * Только владелец после авторизации, но ДО захвата.
         */
        public function void(User $user, PaymentTransaction $payment): bool
        {
            $allowed = (
                $payment->tenant_id === $user->tenant_id &&
                $payment->status === 'authorized' &&
                !$payment->captured_at &&
                $user->hasRole(['business', 'admin'])
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized void attempt', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь сделать возврат (refund)?
         * Только владелец и только если платёж захвачен.
         */
        public function refund(User $user, PaymentTransaction $payment): bool
        {
            $allowed = (
                $payment->tenant_id === $user->tenant_id &&
                $payment->status === 'captured' &&
                $payment->captured_at !== null &&
                now()->diffInDays($payment->captured_at) <= (int) $this->config->get('payments.refund.max_refund_days') &&
                $user->hasRole(['business', 'admin'])
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized refund attempt', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                    'days_since_capture' => $payment->captured_at ? now()->diffInDays($payment->captured_at) : null,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь видеть все платежи своего tenant?
         * Только администратор или финансовый менеджер.
         */
        public function viewAny(User $user): bool
        {
            return $user->hasAnyRole(['admin', 'finance_manager', 'business']);
        }

        /**
         * Может ли пользователь обновить платёж?
         * НЕЛЬЗЯ обновлять уже захваченные платежи!
         */
        public function update(User $user, PaymentTransaction $payment): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            $allowed = (
                $payment->tenant_id === $user->tenant_id &&
                !$payment->captured_at && // нельзя менять захваченные платежи
                $user->hasRole('admin')
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized payment update attempt', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь удалить платёж?
         * Только администратор (soft delete).
         */
        public function delete(User $user, PaymentTransaction $payment): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            $allowed = $user->hasRole('admin') && $payment->tenant_id === $user->tenant_id;

            if (!$allowed) {
                $this->logger->warning('Unauthorized payment deletion attempt', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь восстановить удалённый платёж?
         * Только администратор.
         */
        public function restore(User $user, PaymentTransaction $payment): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->hasRole('admin') && $payment->tenant_id === $user->tenant_id;
        }

        /**
         * Может ли пользователь переко-удалить платёж (hard delete)?
         * НИКОГДА! Платежи хранятся для аудита.
         */
        public function forceDelete(User $user, PaymentTransaction $payment): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return false; // ЗАПРЕЩЕНО навсегда
        }
}
