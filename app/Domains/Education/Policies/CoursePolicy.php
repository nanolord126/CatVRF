<?php

namespace App\Domains\Education\Policies;

use App\Domains\Education\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id === tenant()->id;
    }

    public function view(User $user, Course $course): bool
    {
        return $user->tenant_id === $course->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id === tenant()->id && $user->hasPermissionTo('create_courses');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->tenant_id === $course->tenant_id && 
               ($user->id === $course->instructor_id || $user->hasPermissionTo('update_courses'));
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->tenant_id === $course->tenant_id && 
               ($user->id === $course->instructor_id || $user->hasPermissionTo('delete_courses'));
    }
}
