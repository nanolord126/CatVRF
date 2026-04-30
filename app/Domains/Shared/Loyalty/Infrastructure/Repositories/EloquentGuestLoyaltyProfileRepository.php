<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Domain\Entities\GuestLoyaltyProfile;
use Modules\Loyalty\Domain\Repositories\GuestLoyaltyProfileRepositoryInterface;
use Modules\Loyalty\Infrastructure\Models\GuestLoyaltyProfileModel;

final readonly class EloquentGuestLoyaltyProfileRepository implements GuestLoyaltyProfileRepositoryInterface
{
    public function findById(string $id): ?GuestLoyaltyProfile
    {
        $model = GuestLoyaltyProfileModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?GuestLoyaltyProfile
    {
        $model = GuestLoyaltyProfileModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByGuestAndProgram(int $guestId, string $programId): ?GuestLoyaltyProfile
    {
        $model = GuestLoyaltyProfileModel::where('guest_id', $guestId)
            ->where('loyalty_program_id', $programId)
            ->first();

        return $model?->toDomain();
    }

    public function findByUserAndProgram(int $userId, string $programId): ?GuestLoyaltyProfile
    {
        $model = GuestLoyaltyProfileModel::where('user_id', $userId)
            ->where('loyalty_program_id', $programId)
            ->first();

        return $model?->toDomain();
    }

    public function save(GuestLoyaltyProfile $profile): GuestLoyaltyProfile
    {
        return DB::transaction(function () use ($profile) {
            $model = GuestLoyaltyProfileModel::fromDomain($profile);
            $model->save();

            if ($profile->getId() === '0') {
                $model = GuestLoyaltyProfileModel::find($model->id);
                return $model->toDomain();
            }

            return $model->fresh()->toDomain();
        });
    }

    public function delete(string $id): void
    {
        GuestLoyaltyProfileModel::findOrFail($id)->delete();
    }
}
