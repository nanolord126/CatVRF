<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PerformanceMetricPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function viewAny(User $user): Response
        {
            return $user->auth() ? $this->response->allow() : $this->response->deny();
        }

        public function view(User $user, PerformanceMetric $metric): Response
        {
            return $user->id === $metric->member_id || $user->hasPermissionTo('view_metrics') ? $this->response->allow() : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_metrics') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, PerformanceMetric $metric): Response
        {
            return $user->hasPermissionTo('update_metrics') ? $this->response->allow() : $this->response->deny();
        }
}
