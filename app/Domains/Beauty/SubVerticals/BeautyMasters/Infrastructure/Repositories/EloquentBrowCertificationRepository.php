<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\BrowCertification;
use Modules\BeautyMasters\Domain\Entities\CertificationStatus;
use Modules\BeautyMasters\Domain\Entities\CertificationType;
use Modules\BeautyMasters\Domain\Repositories\BrowCertificationRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\BrowCertificationModel;
use Illuminate\Support\Collection;

final readonly class EloquentBrowCertificationRepository implements BrowCertificationRepositoryInterface
{
    public function findById(int $id): ?BrowCertification
    {
        $model = BrowCertificationModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): Collection
    {
        return BrowCertificationModel::where('master_id', $masterId)
            ->get()
            ->map(fn (BrowCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findActiveByMasterId(int $masterId): Collection
    {
        return BrowCertificationModel::where('master_id', $masterId)
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (BrowCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findActiveByMasterIdAndSpecialization(int $masterId, string $specialization): ?BrowCertification
    {
        $model = BrowCertificationModel::where('master_id', $masterId)
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

        return BrowCertificationModel::where('expiry_date', '<=', $threshold)
            ->where('expiry_date', '>', now()->toDateString())
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (BrowCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findExpired(): Collection
    {
        return BrowCertificationModel::where('expiry_date', '<', now()->toDateString())
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (BrowCertificationModel $model) => $this->modelToEntity($model));
    }

    public function save(BrowCertification $certification): BrowCertification
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
            $model = BrowCertificationModel::create($data);
        } else {
            $model = BrowCertificationModel::findOrFail($certification->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return BrowCertificationModel::destroy($id) > 0;
    }

    private function modelToEntity(BrowCertificationModel $model): BrowCertification
    {
        return new BrowCertification(
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
