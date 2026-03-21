<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Referral\Models\Referral;
use Illuminate\Support\Facades\Log;

/**
 * Referral Authorization Policy
 * CANON 2026 - Production Ready
 *
 * Управление доступом к реферальной программе.
 * Разделяет права рефереров и приглашённых.
 */
final class ReferralPolicy
{
    use HandlesAuthorization;

    /**
     * Может ли пользователь видеть реферальную ссылку?
     * Только свою.
     */
    public function view(User $user, Referral $referral): bool
    {
        // CANON 2026: Strict tenant scoping check
        if (isset($referral->tenant_id) && $user->tenant_id !== $referral->tenant_id && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'model_tenant_id' => $referral->tenant_id,
            ]);
            return false;
        }

        $allowed = (
            $user->id === $referral->referrer_id ||
            $user->id === $referral->referee_id ||
            $user->hasRole('admin')
        );

        if (!$allowed) {
            Log::warning('Unauthorized referral view attempt', [
                'user_id' => $user->id,
                'referral_id' => $referral->id,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь видеть все рефералы?
     * Только администратор.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Может ли пользователь создать реферальную ссылку?
     * Любой авторизованный пользователь.
     */
    public function create(User $user): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass()); // FIXME: DTO needed
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        $allowed = $user->email_verified_at !== null;

        if (!$allowed) {
            Log::info('Unverified user referral creation attempt', [
                'user_id' => $user->id,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь просмотреть свою реферальную статистику?
     */
    public function viewStats(User $user): bool
    {
        return true; // каждый может видеть свою статистику
    }

    /**
     * Может ли пользователь просмотреть бонусы из рефералов?
     */
    public function viewRewards(User $user): bool
    {
        return true; // каждый может видеть свои награды
    }

    /**
     * Может ли пользователь просмотреть список приглашённых?
     */
    public function viewInvitees(User $user): bool
    {
        return true; // рефереры видят своих приглашённых
    }

    /**
     * Может ли пользователь заявить о миграции?
     * С подтверждением источника платформы.
     */
    public function claimMigration(User $user): bool
    {
        return $user->email_verified_at !== null && $user->kyc_verified;
    }

    /**
     * Может ли пользователь загрузить подтверждение миграции?
     * Скриншот, письмо, прочее.
     */
    public function uploadMigrationProof(User $user, Referral $referral): bool
    {
        return (
            $user->id === $referral->referee_id &&
            $referral->status === 'pending' &&
            !$referral->migrated_at
        );
    }

    /**
     * Может ли пользователь просмотреть скидку за миграцию?
     */
    public function viewMigrationDiscount(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    /**
     * Может ли пользователь запросить вывод реферальных бонусов?
     * Только бизнес может выводить деньги.
     * Пользователи могут только тратить.
     */
    public function withdrawRewards(User $user): bool
    {
        return $user->hasRole('business') && $user->kyc_verified;
    }

    /**
     * Может ли пользователь просмотреть условия реферальной программы?
     */
    public function viewTerms(User $user): bool
    {
        return true; // публичная информация
    }

    /**
     * Может ли пользователь просмотреть рейтинг рефереров?
     */
    public function viewLeaderboard(User $user): bool
    {
        return true; // публичная информация
    }

    /**
     * Может ли администратор одобрить миграцию?
     * После проверки документов.
     */
    public function approveMigration(User $user, Referral $referral): bool
    {
        return (
            $user->hasRole('admin') &&
            $referral->status === 'pending' &&
            $referral->migrated_at === null
        );
    }

    /**
     * Может ли администратор отклонить миграцию?
     */
    public function rejectMigration(User $user, Referral $referral): bool
    {
        return (
            $user->hasRole('admin') &&
            $referral->status === 'pending' &&
            !$referral->migrated_at
        );
    }

    /**
     * Может ли администратор выдать дополнительный бонус за реферал?
     * При разборе жалобы.
     */
    public function awardBonus(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Может ли администратор отменить реферальный бонус?
     * При выявлении фрода.
     */
    public function revokeBonus(User $user, Referral $referral): bool
    {
        $allowed = (
            $user->hasRole('admin') &&
            $referral->bonus_amount > 0 &&
            !$referral->bonus_withdrawn_at
        );

        if (!$allowed) {
            Log::warning('Unauthorized referral bonus revocation attempt', [
                'user_id' => $user->id,
                'referral_id' => $referral->id,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли администратор просмотреть полный аудит реферала?
     */
    public function viewAudit(User $user, Referral $referral): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Может ли администратор отключить реферальную программу?
     */
    public function disable(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Может ли администратор удалить запись о реферале?
     * Soft delete для аудита.
     */
    public function delete(User $user, Referral $referral): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass()); // FIXME: DTO needed
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Может ли администратор восстановить реферал?
     */
    public function restore(User $user, Referral $referral): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass()); // FIXME: DTO needed
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Может ли администратор hard-удалить реферал?
     * ЗАПРЕЩЕНО - рефералы хранятся для аудита.
     */
    public function forceDelete(User $user, Referral $referral): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass()); // FIXME: DTO needed
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return false;
    }
}
