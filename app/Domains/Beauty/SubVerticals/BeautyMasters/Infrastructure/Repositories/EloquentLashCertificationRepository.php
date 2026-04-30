<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\LashCertification;
use Modules\BeautyMasters\Domain\Entities\CertificationStatus;
use Modules\BeautyMasters\Domain\Entities\CertificationType;
use Modules\BeautyMasters\Domain\Repositories\LashCertificationRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\LashCertificationModel;
use Illuminate\Support\Collection;

final readonly class EloquentLashCertificationRepository implements LashCertificationRepositoryInterface
{
    public function findById(int $id): ?LashCertification
    {
        $model = LashCertificationModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): Collection
    {
        return LashCertificationModel::where('master_id', $masterId)
            ->get()
            ->map(fn (LashCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findActiveByMasterId(int $masterId): Collection
    {
        return LashCertificationModel::where('master_id', $masterId)
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (LashCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findActiveByMasterIdAndSpecialization(int $masterId, string $specialization): ?LashCertification
    {
        $model = LashCertificationModel::where('master_id', $masterId)
            ->where('status', CertificationStatus::ACTIVE->value)
            ->where('name', 'like', "%{$specialization}%")
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findExpiringSoon(int $daysThreshold = 60): Collection
    {
        $threshold = now()->addDays($daysThreshold)->toDateString();

        return LashCertificationModel::where('expiry_date', '<=', $threshold)
            ->where('expiry_date', '>', now()->toDateString())
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (LashCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findExpired(): Collection
    {
        return LashCertificationModel::where('expiry_date', '<', now()->toDateString())
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (LashCertificationModel $model) => $this->modelToEntity($model));
    }

    public function save(LashCertification $certification): LashCertification
    {
        $data = [
            'master_id' => $certification->masterId,
            'certification_type' => $certification->certificationType->value,
            'name' => $certification->name,
            'issuer' => $certification->issuer,
            'issue_date' => $certification->issueDate->format('Y-m-d'),
            'expiry_date' => $certification->expiryDate?->format('Y-m-d'),
            'certificate_number' => $certification->certificateNumber,
            'document_file' => $certification->documentFile,
            'status' => $certification->status->value,
            'notes' => $certification->notes,
        ];

        if ($certification->id === 0) {
            $model = LashCertificationModel::create($data);
        } else {
            $model = LashCertificationModel::findOrFail($certification->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return LashCertificationModel::destroy($id) > 0;
    }

    private function modelToEntity(LashCertificationModel $model): LashCertification
    {
        return new LashCertification(
            id: $model->id,
            masterId: $model->master_id,
            certificationType: CertificationType::from($model->certification_type),
            name: $model->name,
            issuer: $model->issuer,
            issueDate: new \DateTimeImmutable($model->issue_date->format('Y-m-d')),
            expiryDate: $model->expiry_date ? new \DateTimeImmutable($model->expiry_date->format('Y-m-d')) : null,
            certificateNumber: $model->certificate_number,
            documentFile: $model->document_file,
            status: CertificationStatus::from($model->status),
            notes: $model->notes,
            createdAt: new \DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null,
        );
    }
}
