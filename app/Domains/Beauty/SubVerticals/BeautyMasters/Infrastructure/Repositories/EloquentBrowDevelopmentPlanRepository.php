<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\BrowDevelopmentPlan;
use Modules\BeautyMasters\Domain\Repositories\BrowDevelopmentPlanRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\BrowDevelopmentPlanModel;

final readonly class EloquentBrowDevelopmentPlanRepository implements BrowDevelopmentPlanRepositoryInterface
{
    public function findById(int $id): ?BrowDevelopmentPlan
    {
        $model = BrowDevelopmentPlanModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): ?BrowDevelopmentPlan
    {
        $model = BrowDevelopmentPlanModel::where('master_id', $masterId)->first();
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function save(BrowDevelopmentPlan $plan): BrowDevelopmentPlan
    {
        $data = [
            'master_id' => $plan->masterId,
            'required_courses' => $plan->requiredCourses,
            'progress_percent' => $plan->progressPercent,
            'next_certification_date' => $plan->nextCertificationDate?->format('Y-m-d'),
            'mentor_notes' => $plan->mentorNotes,
        ];

        if ($plan->id === 0) {
            $model = BrowDevelopmentPlanModel::create($data);
        } else {
            $model = BrowDevelopmentPlanModel::findOrFail($plan->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return BrowDevelopmentPlanModel::destroy($id) > 0;
    }

    private function modelToEntity(BrowDevelopmentPlanModel $model): BrowDevelopmentPlan
    {
        return new BrowDevelopmentPlan(
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
