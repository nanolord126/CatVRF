<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Models\Master;
use Illuminate\Database\DatabaseManager;
use App\Services\AuditService;
use Illuminate\Contracts\Auth\Guard;

final class UpdateMasterAction
{
    public function __construct(
        private DatabaseManager $db,
        private AuditService $audit,
        private Guard $guard,
    ) {}

    public function execute(int $tenantId, int $masterId, array $data): Master
    {
        return $this->db->transaction(function () use ($tenantId, $masterId, $data) {
            $master = Master::whereHas('salon', fn ($q) => $q->where('tenant_id', $tenantId))
                ->findOrFail($masterId);

            $old = $master->toArray();

            $master->update($data);

            $this->audit->record(
                action: 'staff_updated',
                subjectType: Master::class,
                subjectId: $master->id,
                oldValues: $old,
                newValues: $master->fresh()->toArray(),
                correlationId: $data['correlation_id'] ?? null
            );

            return $master->fresh();
        });
    }
}
