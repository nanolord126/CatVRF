<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\CertificationLevel;
use Modules\BeautyMasters\Domain\Entities\CertificationTestResult;
use Modules\BeautyMasters\Domain\Repositories\CertificationTestResultRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\CertificationTestResultModel;
use Illuminate\Support\Collection;

final readonly class EloquentCertificationTestResultRepository implements CertificationTestResultRepositoryInterface
{
    public function findById(int $id): ?CertificationTestResult
    {
        $model = CertificationTestResultModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): Collection
    {
        return CertificationTestResultModel::where('master_id', $masterId)
            ->get()
            ->map(fn (CertificationTestResultModel $model) => $this->modelToEntity($model));
    }

    public function findByMasterIdAndVertical(int $masterId, string $vertical): Collection
    {
        return CertificationTestResultModel::where('master_id', $masterId)
            ->where('vertical', $vertical)
            ->get()
            ->map(fn (CertificationTestResultModel $model) => $this->modelToEntity($model));
    }

    public function save(CertificationTestResult $result): CertificationTestResult
    {
        $data = [
            'master_id' => $result->masterId,
            'test_name' => $result->testName,
            'vertical' => $result->vertical,
            'theory_score' => $result->theoryScore,
            'practice_score' => $result->practiceScore,
            'total_score' => $result->totalScore,
            'passed' => $result->passed,
            'awarded_level' => $result->awardedLevel?->value,
            'practical_work_photos' => $result->practicalWorkPhotos,
            'feedback' => $result->feedback,
            'completed_at' => $result->completedAt->format('Y-m-d H:i:s'),
        ];

        if ($result->id === 0) {
            $model = CertificationTestResultModel::create($data);
        } else {
            $model = CertificationTestResultModel::findOrFail($result->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return CertificationTestResultModel::destroy($id) > 0;
    }

    private function modelToEntity(CertificationTestResultModel $model): CertificationTestResult
    {
        return new CertificationTestResult(
            id: $model->id,
            masterId: $model->master_id,
            testName: $model->test_name,
            vertical: $model->vertical,
            theoryScore: $model->theory_score,
            practiceScore: $model->practice_score,
            totalScore: $model->total_score,
            passed: $model->passed,
            awardedLevel: $model->awarded_level ? CertificationLevel::from($model->awarded_level) : null,
            practicalWorkPhotos: $model->practical_work_photos,
            feedback: $model->feedback,
            completedAt: new \DateTimeImmutable($model->completed_at->format('Y-m-d H:i:s')),
            createdAt: new \DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
        );
    }
}
