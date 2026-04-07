<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Persistence;

use Modules\Bonuses\Domain\Entities\Bonus;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class EloquentBonusRepository implements BonusRepositoryInterface
{
    public function create(array $data): Bonus
    {
        $bonus = Bonus::create($data);
        Cache::forget('bonuses_for_user:' . $bonus->user_id);
        return $bonus;
    }

    public function findById(int $id): ?Bonus
    {
        return Bonus::find($id);
    }

    public function getForUser(int $userId): Collection
    {
        return Cache::remember('bonuses_for_user:' . $userId, 3600, function () use ($userId) {
            return Bonus::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        });
    }
}
