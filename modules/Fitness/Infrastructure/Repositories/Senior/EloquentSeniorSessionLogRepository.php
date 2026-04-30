<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Senior;

use Modules\Fitness\Domain\Senior\Entities\SeniorSessionLog;
use Modules\Fitness\Domain\Senior\Repositories\SeniorSessionLogRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Senior\SeniorSessionLogModel;

final readonly class EloquentSeniorSessionLogRepository implements SeniorSessionLogRepositoryInterface
{
    public function save(SeniorSessionLog $log): SeniorSessionLog
    {
        $model = $log->id === 0
            ? SeniorSessionLogModel::fromDomain($log)
            : SeniorSessionLogModel::where('id', $log->id)->first();

        if ($model === null) {
            $model = SeniorSessionLogModel::fromDomain($log);
        } else {
            $model->updateFromDomain($log);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?SeniorSessionLog
    {
        $model = SeniorSessionLogModel::find($id);

        return $model?->toDomain();
    }

    public function findByEnrollmentId(int $enrollmentId): array
    {
        $models = SeniorSessionLogModel::where('enrollment_id', $enrollmentId)
            ->orderBy('session_date', 'desc')
            ->get();

        return $models->map(fn (SeniorSessionLogModel $model) => $model->toDomain())->toArray();
    }

    public function findByEnrollmentIdAndDate(int $enrollmentId, string $date): ?SeniorSessionLog
    {
        $model = SeniorSessionLogModel::where('enrollment_id', $enrollmentId)
            ->where('session_date', $date)
            ->first();

        return $model?->toDomain();
    }

    public function findByEnrollmentIdAndDateRange(int $enrollmentId, string $startDate, string $endDate): array
    {
        $models = SeniorSessionLogModel::where('enrollment_id', $enrollmentId)
            ->where('session_date', '>=', $startDate)
            ->where('session_date', '<=', $endDate)
            ->orderBy('session_date')
            ->get();

        return $models->map(fn (SeniorSessionLogModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        SeniorSessionLogModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return SeniorSessionLogModel::where('id', $id)->exists();
    }
}
