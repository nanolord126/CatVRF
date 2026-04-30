<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Kids;

use Modules\Fitness\Domain\Kids\Entities\KidsSessionLog;
use Modules\Fitness\Domain\Kids\Repositories\KidsSessionLogRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Kids\KidsSessionLogModel;

final readonly class EloquentKidsSessionLogRepository implements KidsSessionLogRepositoryInterface
{
    public function save(KidsSessionLog $log): KidsSessionLog
    {
        $model = $log->id > 0
            ? KidsSessionLogModel::findOrFail($log->id)
            : new KidsSessionLogModel();

        if ($log->id > 0) {
            $model->updateFromDomain($log);
        } else {
            $model = KidsSessionLogModel::fromDomain($log);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?KidsSessionLog
    {
        $model = KidsSessionLogModel::find($id);
        return $model?->toDomain();
    }

    public function findByEnrollmentId(int $enrollmentId): array
    {
        $models = KidsSessionLogModel::where('enrollment_id', $enrollmentId)
            ->orderBy('session_date', 'desc')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByEnrollmentIdAndDate(int $enrollmentId, string $date): ?KidsSessionLog
    {
        $model = KidsSessionLogModel::where('enrollment_id', $enrollmentId)
            ->where('session_date', $date)
            ->first();
        return $model?->toDomain();
    }

    public function findByEnrollmentIdAndDateRange(int $enrollmentId, string $startDate, string $endDate): array
    {
        $models = KidsSessionLogModel::where('enrollment_id', $enrollmentId)
            ->whereBetween('session_date', [$startDate, $endDate])
            ->orderBy('session_date', 'desc')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        KidsSessionLogModel::findOrFail($id)->delete();
    }

    public function exists(int $id): bool
    {
        return KidsSessionLogModel::where('id', $id)->exists();
    }
}
