<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Models\Master;
use Illuminate\Database\DatabaseManager;
use App\Services\AuditService;

final class CreateMasterAction
{
    public function __construct(
        private DatabaseManager $db,
        private AuditService $audit,
    ) {}

    public function execute(int $tenantId, array $data): Master
    {
        return $this->db->transaction(function () use ($data, $tenantId) {
            $master = Master::create([
                'salon_id' => $data['salon_id'],
                'full_name' => $data['full_name'],
                'specialization' => $data['specialization'] ?? null,
                'rating' => $data['rating'] ?? 5.0,
                'is_active' => $data['is_active'] ?? true,
                'tags' => $data['tags'] ?? [],
                'tenant_id' => $tenantId,
            ]);

            $this->audit->record(
                action: 'staff_created',
                subjectType: Master::class,
                subjectId: $master->id,
                newValues: $master->toArray(),
                correlationId: $data['correlation_id'] ?? null
            );

            return $master;
        });
    }
}
