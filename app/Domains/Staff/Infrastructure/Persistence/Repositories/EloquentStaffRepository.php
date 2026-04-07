<?php

declare(strict_types=1);

namespace App\Domains\Staff\Infrastructure\Persistence\Repositories;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Staff\Domain\DTOs\StaffData;
use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Repositories\StaffRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

final class EloquentStaffRepository implements StaffRepositoryInterface
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Guard $guard) {}

    public function create(StaffData $data): Staff
    {
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
        return $this->db->transaction(function () use ($data) {
            return Staff::create([
                'user_id' => $data->user_id,
                'tenant_id' => $data->tenant_id,
                'role' => $data->role,
                'correlation_id' => $data->correlation_id ?? Str::uuid()->toString(),
                'uuid' => Str::uuid()->toString(),
            ]);
        });
    }

    public function findById(string $id): ?Staff
    {
        return Staff::find($id);
    }

    public function update(string $id, array $data): bool
    {
        return (bool) $this->db->transaction(function () use ($id, $data) {
            return Staff::where('id', $id)->update($data);
        });
    }

    public function delete(string $id): bool
    {
        return (bool) $this->db->transaction(function () use ($id) {
            return Staff::destroy($id);
        });
    }

    public function getByTenant(int $tenantId): Collection
    {
        return Staff::where('tenant_id', $tenantId)->with('user')->get();
    }
}
