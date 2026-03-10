<?php

namespace App\Policies;

use App\Models\HRJobVacancy;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HRJobVacancyPolicy extends BaseSecurityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_hr_job_vacancy');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, HRJobVacancy $vacancy): bool
    {
        return $user->hasPermissionTo('view_hr_job_vacancy');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_hr_job_vacancy');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, HRJobVacancy $vacancy): bool
    {
        return $user->hasPermissionTo('update_hr_job_vacancy');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HRJobVacancy $vacancy): bool
    {
        return $user->hasPermissionTo('delete_hr_job_vacancy');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, HRJobVacancy $vacancy): bool
    {
        return $user->hasPermissionTo('restore_hr_job_vacancy');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, HRJobVacancy $vacancy): bool
    {
        return $user->hasPermissionTo('force_delete_hr_job_vacancy');
    }
}

