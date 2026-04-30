<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\BeautyCertification;
use Modules\BeautyMasters\Domain\Entities\CertificationStatus;
use Modules\BeautyMasters\Domain\Entities\CertificationType;
use Modules\BeautyMasters\Domain\Repositories\BeautyCertificationRepositoryInterface;
use Modules\BeautyMasters\Infrastructure\Models\BeautyCertificationModel;
use Illuminate\Support\Collection;

final readonly class EloquentBeautyCertificationRepository implements BeautyCertificationRepositoryInterface
{
    public function findById(int $id): ?BeautyCertification
    {
        $model = BeautyCertificationModel::find($id);
        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByMasterId(int $masterId): Collection
    {
        return BeautyCertificationModel::where('master_id', $masterId)
            ->get()
            ->map(fn (BeautyCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findActiveByMasterId(int $masterId): Collection
    {
        return BeautyCertificationModel::where('master_id', $masterId)
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (BeautyCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findActiveByMasterIdAndSpecialization(int $masterId, string $specialization): ?BeautyCertification
    {
        $model = BeautyCertificationModel::where('master_id', $masterId)
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

        return BeautyCertificationModel::where('expiry_date', '<=', $threshold)
            ->where('expiry_date', '>', now()->toDateString())
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (BeautyCertificationModel $model) => $this->modelToEntity($model));
    }

    public function findExpired(): Collection
    {
        return BeautyCertificationModel::where('expiry_date', '<', now()->toDateString())
            ->where('status', CertificationStatus::ACTIVE->value)
            ->get()
            ->map(fn (BeautyCertificationModel $model) => $this->modelToEntity($model));
    }

    public function save(BeautyCertification $certification): BeautyCertification
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
            $model = BeautyCertificationModel::create($data);
        } else {
            $model = BeautyCertificationModel::findOrFail($certification->id);
            $model->update($data);
        }

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return BeautyCertificationModel::destroy($id) > 0;
    }

    private function modelToEntity(BeautyCertificationModel $model): BeautyCertification
    {
        return new BeautyCertification(
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
