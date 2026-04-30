<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\LashDevelopmentPlan;
use Modules\BeautyMasters\Domain\Repositories\LashDevelopmentPlanRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\LashDevelopmentPlanModel;

final readonly class EloquentLashDevelopmentPlanRepository implements LashDevelopmentPlanRepositoryInterface
{
    public function findById(int $id): ?LashDevelopmentPlan
    {
        $model = LashDevelopmentPlanModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): ?LashDevelopmentPlan
    {
        $model = LashDevelopmentPlanModel::where('master_id', $masterId)->first();
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function save(LashDevelopmentPlan $plan): LashDevelopmentPlan
    {
        $data = [
            'master_id' => $plan->masterId,
            'required_courses' => $plan->requiredCourses,
            'progress_percent' => $plan->progressPercent,
            'next_certification_date' => $plan->nextCertificationDate?->format('Y-m-d'),
            'mentor_notes' => $plan->mentorNotes,
        ];

        if ($plan->id === 0) {
            $model = LashDevelopmentPlanModel::create($data);
        } else {
            $model = LashDevelopmentPlanModel::findOrFail($plan->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return LashDevelopmentPlanModel::destroy($id) > 0;
    }

    private function modelToEntity(LashDevelopmentPlanModel $model): LashDevelopmentPlan
    {
        return new LashDevelopmentPlan(
            id: $model->id,
            masterId: $model->master_id,
            requiredCourses: $model->required_courses,
            progressPercent: $model->progress_percent,
            nextCertificationDate: $model->next_certification_date ? new \DateTimeImmutable($model->next_certification_date->format('Y-m-d')) : null,
            mentorNotes: $model->mentor_notes,
            createdAt: new \DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null,
        );
    }
}
