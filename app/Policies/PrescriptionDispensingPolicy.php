<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Prescription Dispensing Policy
 * 
 * Политика доступа для отпуска рецептурных препаратов
 * Реализует segregation of duties для ФЗ-323 compliance
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class PrescriptionDispensingPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр истории отпуска
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('prescription_dispensing.view');
    }

    /**
     * Отпуск рецептурного препарата
     */
    public function dispense(User $user): bool
    {
        return $user->hasPermission('prescription_dispensing.dispense');
    }

    /**
     * Отпуск наркотических препаратов (требует специальную роль)
     */
    public function dispenseNarcotic(User $user): bool
    {
        return $user->hasPermission('prescription_dispensing.dispense_narcotic')
            && $this->hasNarcoticLicense($user);
    }

    /**
     * Отпуск психотропных препаратов
     */
    public function dispensePsychotropic(User $user): bool
    {
        return $user->hasPermission('prescription_dispensing.dispense_psychotropic')
            && $this->hasPsychotropicLicense($user);
    }

    /**
     * Двойной контроль для контролируемых веществ
     */
    public function requireDualControl(User $user, string $drugCategory): bool
    {
        if (in_array($drugCategory, ['narcotic', 'psychotropic'])) {
            return $user->hasPermission('prescription_dispensing.dual_control');
        }

        return true;
    }

    /**
     * Проверка наличия лицензии на наркотические вещества
     */
    private function hasNarcoticLicense(User $user): bool
    {
        return $user->hasPermission('narcotic_handling');
    }

    /**
     * Проверка наличия лицензии на психотропные вещества
     */
    private function hasPsychotropicLicense(User $user): bool
    {
        return $user->hasPermission('psychotropic_handling');
    }
}
