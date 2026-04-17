<?php declare(strict_types=1);

namespace App\Domains\Education\Policies;

use App\Domains\Education\Models\VerticalCourse;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class VerticalCoursePolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка курсов по вертикалям
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_vertical_courses') 
            || $user->hasRole('admin')
            || $user->hasRole('education_manager');
    }

    /**
     * Просмотр конкретного курса вертикали
     */
    public function view(User $user, VerticalCourse $verticalCourse): bool
    {
        // Проверка тенанта
        if ($verticalCourse->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermissionTo('view_vertical_courses')
            || $user->hasRole('admin')
            || $user->hasRole('education_manager');
    }

    /**
     * Создание курса вертикали
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_vertical_courses')
            || $user->hasRole('admin')
            || $user->hasRole('education_manager');
    }

    /**
     * Обновление курса вертикали
     */
    public function update(User $user, VerticalCourse $verticalCourse): bool
    {
        // Проверка тенанта
        if ($verticalCourse->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermissionTo('update_vertical_courses')
            || $user->hasRole('admin')
            || $user->hasRole('education_manager');
    }

    /**
     * Удаление курса вертикали
     */
    public function delete(User $user, VerticalCourse $verticalCourse): bool
    {
        // Проверка тенанта
        if ($verticalCourse->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermissionTo('delete_vertical_courses')
            || $user->hasRole('admin')
            || $user->hasRole('education_manager');
    }

    /**
     * Восстановление удаленного курса вертикали
     */
    public function restore(User $user, VerticalCourse $verticalCourse): bool
    {
        // Проверка тенанта
        if ($verticalCourse->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermissionTo('restore_vertical_courses')
            || $user->hasRole('admin');
    }

    /**
     * Принудительное удаление курса вертикали
     */
    public function forceDelete(User $user, VerticalCourse $verticalCourse): bool
    {
        // Проверка тенанта
        if ($verticalCourse->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Зачисление сотрудника на курс вертикали
     */
    public function enroll(User $user, VerticalCourse $verticalCourse): bool
    {
        // Проверка тенанта
        if ($verticalCourse->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermissionTo('enroll_employees_vertical_courses')
            || $user->hasRole('admin')
            || $user->hasRole('education_manager')
            || $user->hasRole('hr_manager');
    }

    /**
     * Просмотр прогресса обучения по вертикали
     */
    public function viewProgress(User $user, string $vertical): bool
    {
        return $user->hasPermissionTo('view_vertical_training_progress')
            || $user->hasRole('admin')
            || $user->hasRole('education_manager')
            || $user->hasRole('hr_manager');
    }
}
