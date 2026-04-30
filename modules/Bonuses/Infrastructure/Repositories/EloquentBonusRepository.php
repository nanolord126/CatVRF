<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Repositories;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Modules\Bonuses\Domain\Entities\BonusAggregate;
use Modules\Bonuses\Domain\Entities\BonusProgram;
use Modules\Bonuses\Domain\Enums\BonusStatus;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Domain\Repositories\BonusProgramRepositoryInterface;
use Modules\Bonuses\Domain\Repositories\LoyaltyStatusRepositoryInterface;
use Modules\Bonuses\Domain\ValueObjects\LoyaltyStatus;

/**
 * Class EloquentBonusRepository
 *
 * Eloquent implementation of BonusRepositoryInterface.
 * Uses database transactions and locking for data integrity.
 * Implements cache tags for efficient cache invalidation.
 */
final class EloquentBonusRepository implements BonusRepositoryInterface
{
    private const CACHE_TAGS = ['bonuses'];
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ConnectionInterface $connection
    ) {}

    public function findById(string $id): ?BonusAggregate
    {
        $cacheKey = "bonus:{$id}";

        $record = cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return DB::table('bonuses')
                ->where('id', $id)
                ->first();
        });

        if (!$record) {
            return null;
        }

        return BonusAggregate::fromArray((array) $record);
    }

    public function findActiveByOwnerId(string $ownerId, ?BonusType $type = null): array
    {
        $cacheKey = "bonuses_active:{$ownerId}:" . ($type ? $type->value : 'all');

        return cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($ownerId, $type) {
            $query = DB::table('bonuses')
                ->where('owner_id', $ownerId)
                ->where('status', BonusStatus::ACTIVE->value)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });

            if ($type !== null) {
                $query->where('type', $type->value);
            }

            $records = $query->orderBy('created_at')->get();

            return array_map(
                fn ($record) => BonusAggregate::fromArray((array) $record),
                $records->toArray()
            );
        });
    }

    public function findByOwnerId(string $ownerId, ?BonusStatus $status = null): array
    {
        $query = DB::table('bonuses')->where('owner_id', $ownerId);

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        return array_map(
            fn ($record) => BonusAggregate::fromArray((array) $record),
            $records->toArray()
        );
    }

    public function findExpiringWithinDays(int $daysWithin, BonusStatus $status = BonusStatus::ACTIVE): array
    {
        $cutoffDate = now()->addDays($daysWithin);

        $records = DB::table('bonuses')
            ->where('status', $status->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $cutoffDate)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->get();

        return array_map(
            fn ($record) => BonusAggregate::fromArray((array) $record),
            $records->toArray()
        );
    }

    public function findExpiredBefore(DateTimeImmutable $before): array
    {
        $records = DB::table('bonuses')
            ->where('status', BonusStatus::ACTIVE->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $before->format('Y-m-d H:i:s'))
            ->get();

        return array_map(
            fn ($record) => BonusAggregate::fromArray((array) $record),
            $records->toArray()
        );
    }

    public function findFrozen(int $limit = 100): array
    {
        $records = DB::table('bonuses')
            ->where('status', BonusStatus::FROZEN->value)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return array_map(
            fn ($record) => BonusAggregate::fromArray((array) $record),
            $records->toArray()
        );
    }

    public function save(BonusAggregate $bonus): void
    {
        $data = $bonus->toArray();
        $data['updated_at'] = now()->toDateTimeString();

        $exists = DB::table('bonuses')->where('id', $bonus->getId())->exists();

        if ($exists) {
            DB::table('bonuses')
                ->where('id', $bonus->getId())
                ->update($data);
        } else {
            $data['created_at'] = now()->toDateTimeString();
            DB::table('bonuses')->insert($data);
        }

        // Invalidate specific bonus cache and owner-specific caches
        cache()->tags(self::CACHE_TAGS)->flush();
        cache()->forget("bonus_balance:{$bonus->getOwnerId()}");
        cache()->forget("bonuses_for_owner:{$bonus->getOwnerId()}");
    }

    public function saveMany(array $bonuses): void
    {
        $this->connection->transaction(function () use ($bonuses) {
            foreach ($bonuses as $bonus) {
                $this->save($bonus);
            }
        });

        cache()->tags(self::CACHE_TAGS)->flush();
    }

    public function lockById(string $id): ?BonusAggregate
    {
        $record = DB::table('bonuses')
            ->where('id', $id)
            ->lockForUpdate()
            ->first();

        if (!$record) {
            return null;
        }

        return BonusAggregate::fromArray((array) $record);
    }

    public function lockByIds(array $ids): array
    {
        $records = DB::table('bonuses')
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();

        return array_map(
            fn ($record) => BonusAggregate::fromArray((array) $record),
            $records->toArray()
        );
    }

    public function getAvailableBalance(string $ownerId): int
    {
        $cacheKey = "bonus_balance:{$ownerId}";

        return (int) cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($ownerId) {
            return DB::table('bonuses')
                ->where('owner_id', $ownerId)
                ->where('status', BonusStatus::ACTIVE->value)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->sum('remaining_amount');
        });
    }

    public function getBalanceByType(string $ownerId): array
    {
        $results = DB::table('bonuses')
            ->where('owner_id', $ownerId)
            ->where('status', BonusStatus::ACTIVE->value)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->select('type', DB::raw('SUM(remaining_amount) as total'))
            ->groupBy('type')
            ->get();

        $balances = [];
        foreach ($results as $result) {
            $balances[$result->type] = (int) $result->total;
        }

        return $balances;
    }

    public function countByStatus(BonusStatus $status): int
    {
        return DB::table('bonuses')
            ->where('status', $status->value)
            ->count();
    }

    public function findByCorrelationId(string $correlationId): array
    {
        $records = DB::table('bonuses')
            ->where('correlation_id', $correlationId)
            ->orderBy('created_at', 'desc')
            ->get();

        return array_map(
            fn ($record) => BonusAggregate::fromArray((array) $record),
            $records->toArray()
        );
    }

    public function findBySource(string $sourceId, string $sourceType): array
    {
        $records = DB::table('bonuses')
            ->where('source_id', $sourceId)
            ->where('source_type', $sourceType)
            ->orderBy('created_at', 'desc')
            ->get();

        return array_map(
            fn ($record) => BonusAggregate::fromArray((array) $record),
            $records->toArray()
        );
    }

    public function delete(string $id): void
    {
        DB::table('bonuses')->where('id', $id)->delete();
        cache()->tags(self::CACHE_TAGS)->flush();
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $query = DB::table('bonuses');

        foreach ($filters as $key => $value) {
            if ($value !== null) {
                $query->where($key, $value);
            }
        }

        $total = $query->count();
        $records = $query
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'data' => array_map(
                fn ($record) => BonusAggregate::fromArray((array) $record),
                $records->toArray()
            ),
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];
    }
}

/**
 * Class EloquentBonusProgramRepository
 *
 * Eloquent implementation of BonusProgramRepositoryInterface.
 */
final class EloquentBonusProgramRepository implements BonusProgramRepositoryInterface
{
    private const CACHE_TAGS = ['bonus_programs'];
    private const CACHE_TTL = 3600;

    public function findById(string $id): ?BonusProgram
    {
        $cacheKey = "bonus_program:{$id}";

        $record = cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return DB::table('bonus_programs')->where('id', $id)->first();
        });

        if (!$record) {
            return null;
        }

        return BonusProgram::fromArray((array) $record);
    }

    public function findByCode(string $code): ?BonusProgram
    {
        $cacheKey = "bonus_program_code:{$code}";

        $record = cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($code) {
            return DB::table('bonus_programs')->where('code', $code)->first();
        });

        if (!$record) {
            return null;
        }

        return BonusProgram::fromArray((array) $record);
    }

    public function findActive(DateTimeImmutable $now = new DateTimeImmutable()): array
    {
        $cacheKey = "bonus_programs_active";

        return cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($now) {
            $records = DB::table('bonus_programs')
                ->where('is_active', true)
                ->where('start_date', '<=', $now->format('Y-m-d H:i:s'))
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>', $now->format('Y-m-d H:i:s'));
                })
                ->get();

            return array_map(
                fn ($record) => BonusProgram::fromArray((array) $record),
                $records->toArray()
            );
        });
    }

    public function findByType(BonusType $type): array
    {
        $cacheKey = "bonus_programs_type:{$type->value}";

        return cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            $records = DB::table('bonus_programs')
                ->where('type', $type->value)
                ->get();

            return array_map(
                fn ($record) => BonusProgram::fromArray((array) $record),
                $records->toArray()
            );
        });
    }

    public function save(BonusProgram $program): void
    {
        $data = $program->toArray();
        $data['updated_at'] = now()->toDateTimeString();

        $exists = DB::table('bonus_programs')->where('id', $program->getId())->exists();

        if ($exists) {
            DB::table('bonus_programs')
                ->where('id', $program->getId())
                ->update($data);
        } else {
            $data['created_at'] = now()->toDateTimeString();
            DB::table('bonus_programs')->insert($data);
        }

        cache()->tags(self::CACHE_TAGS)->flush();
    }

    public function delete(string $id): void
    {
        DB::table('bonus_programs')->where('id', $id)->delete();
        cache()->tags(self::CACHE_TAGS)->flush();
    }
}

/**
 * Class EloquentLoyaltyStatusRepository
 *
 * Eloquent implementation of LoyaltyStatusRepositoryInterface.
 */
final class EloquentLoyaltyStatusRepository implements LoyaltyStatusRepositoryInterface
{
    private const CACHE_TAGS = ['loyalty_status'];
    private const CACHE_TTL = 3600;

    public function findByOwnerId(string $ownerId): ?LoyaltyStatus
    {
        $cacheKey = "loyalty_status:{$ownerId}";

        $record = cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($ownerId) {
            return DB::table('loyalty_status')->where('owner_id', $ownerId)->first();
        });

        if (!$record) {
            return null;
        }

        return LoyaltyStatus::fromArray((array) $record);
    }

    public function save(string $ownerId, LoyaltyStatus $status): void
    {
        $data = $status->toArray();
        $data['owner_id'] = $ownerId;
        $data['updated_at'] = now()->toDateTimeString();

        $exists = DB::table('loyalty_status')->where('owner_id', $ownerId)->exists();

        if ($exists) {
            DB::table('loyalty_status')
                ->where('owner_id', $ownerId)
                ->update($data);
        } else {
            $data['created_at'] = now()->toDateTimeString();
            DB::table('loyalty_status')->insert($data);
        }

        cache()->tags(self::CACHE_TAGS)->flush();
        cache()->forget("loyalty_status:{$ownerId}");
    }

    public function findOwnersByTier(string $tier, int $limit = 1000): array
    {
        $cacheKey = "loyalty_tier_owners:{$tier}:{$limit}";

        return cache()->tags(self::CACHE_TAGS)->remember($cacheKey, self::CACHE_TTL, function () use ($tier, $limit) {
            return DB::table('loyalty_status')
                ->where('tier', $tier)
                ->limit($limit)
                ->pluck('owner_id')
                ->toArray();
        });
    }

    public function resetMonthlyTracking(): int
    {
        $affected = DB::table('loyalty_status')
            ->update([
                'points_earned_this_month' => 0,
                'updated_at' => now()->toDateTimeString(),
            ]);

        cache()->tags(self::CACHE_TAGS)->flush();

        return $affected;
    }
}
