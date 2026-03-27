<?php declare(strict_types=1);

namespace App\Policies\Beauty;

use App\Models\User;
use App\Domains\Beauty\Models\BeautyService;
use App\Services\FraudControlService;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * КАНОН 2026: Beauty Service Policy (Layer 5)
 */
final class BeautyServicePolicy
{
    use HandlesAuthorization;

    public function view(User $user, BeautyService $service): bool
    {
        return true; // Публичные услуги доступны всем
    }

    public function create(User $user): bool
    {
        // Только мастера или салоны, прошедшие фрод-контроль
        return FraudControlService::check(
            userId: $user->id,
            operationType: 'beauty_service_create',
            amount: 0
        );
    }

    public function update(User $user, BeautyService $service): bool
    {
        // Обновлять может мастер (если привязан) или владелец салона
        return $user->id === $service->master_id 
            || $user->id === $service->salon->owner_id;
    }

    public function delete(User $user, BeautyService $service): bool
    {
        return $user->id === $service->salon->owner_id;
    }
}
