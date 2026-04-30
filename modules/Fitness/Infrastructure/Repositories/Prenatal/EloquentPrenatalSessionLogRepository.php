<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Prenatal;

use Modules\Fitness\Domain\Prenatal\Entities\PrenatalSessionLog;
use Modules\Fitness\Domain\Prenatal\Repositories\PrenatalSessionLogRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Prenatal\PrenatalSessionLogModel;

final readonly class EloquentPrenatalSessionLogRepository implements PrenatalSessionLogRepositoryInterface
{
    public function save(PrenatalSessionLog $log): PrenatalSessionLog
    {
        $model = $log->id > 0
            ? PrenatalSessionLogModel::findOrFail($log->id)
            : new PrenatalSessionLogModel();

        if ($log->id > 0) {
            $model->updateFromDomain($log);
        } else {
            $model = PrenatalSessionLogModel::fromDomain($log);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?PrenatalSessionLog
    {
        $model = PrenatalSessionLogModel::find($id);
        return $model?->toDomain();
    }

    public function findByEnrollmentId(int $enrollmentId): array
    {
        $models = PrenatalSessionLogModel::where('enrollment_id', $enrollmentId)
            ->orderBy('session_date', 'desc')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByEnrollmentIdAndDate(int $enrollmentId, string $date): ?PrenatalSessionLog
    {
        $model = PrenatalSessionLogModel::where('enrollment_id', $enrollmentId)
            ->where('session_date', $date)
            ->first();
        return $model?->toDomain();
    }

    public function findByEnrollmentIdAndDateRange(int $enrollmentId, string $startDate, string $endDate): array
    {
        $models = PrenatalSessionLogModel::where('enrollment_id', $enrollmentId)
            ->whereBetween('session_date', [$startDate, $endDate])
            ->orderBy('session_date', 'desc')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        PrenatalSessionLogModel::findOrFail($id)->delete();
    }

    public function exists(int $id): bool
    {
        return PrenatalSessionLogModel::where('id', $id)->exists();
    }
}
