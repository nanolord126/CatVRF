<?php declare(strict_types=1);

namespace App\Domains\Fitness\Policies;

use App\Domains\Fitness\Models\PerformanceMetric;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class PerformanceMetricPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        return $user->auth() ? Response::allow() : Response::deny();
    }

    public function view(User $user, PerformanceMetric $metric): Response
    {
        return $user->id === $metric->member_id || $user->hasPermissionTo('view_metrics') ? Response::allow() : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_metrics') ? Response::allow() : Response::deny();
    }

    public function update(User $user, PerformanceMetric $metric): Response
    {
        return $user->hasPermissionTo('update_metrics') ? Response::allow() : Response::deny();
    }
}
