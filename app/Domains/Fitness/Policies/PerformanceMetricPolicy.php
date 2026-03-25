declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Policies;

use App\Domains\Fitness\Models\PerformanceMetric;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final /**
 * PerformanceMetricPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PerformanceMetricPolicy
{
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
