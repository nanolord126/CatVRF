<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Persistence;

use Modules\Bonuses\Domain\Entities\Bonus;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Cache\Repository;

final class EloquentBonusRepository implements BonusRepositoryInterface
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly Repository $cache,
    ) {}
    public function create(array $data): Bonus
    {
        $bonus = Bonus::create($data);
        $this->cache->forget('bonuses_for_user:'.$bonus->user_id);

        return $bonus;
    }

    public function findById(int $id): ?Bonus
    {
        return Bonus::find($id);
    }

    public function getForUser(int $userId): Collection
    {
        return $this->cache->remember('bonuses_for_user:'.$userId, self::CACHE_TTL, function () use ($userId) {
            return Bonus::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        });
    }
}
