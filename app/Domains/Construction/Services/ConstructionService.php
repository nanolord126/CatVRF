<?php

declare(strict_types=1);

namespace App\Domains\Construction\Services;

use App\Domains\Construction\Models\ConstructionProject;
use App\Domains\Construction\Models\ConstructionMaterial;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис управления строительными проектами (Раздел 5)
 */
final class ConstructionService
{
    private string $correlationId;

    public function __construct(?string $correlationId = null)
    {
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * Создание нового строительного проекта
     */
    public function createProject(array $data, int $tenantId): ConstructionProject
    {
        // 1. Fraud Check (защита от массового создания фейковых строек)
        FraudControlService::check($this->correlationId);

        return $this->db->transaction(function () use ($data, $tenantId) {
            $project = ConstructionProject::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $data['business_group_id'] ?? null,
                'client_id' => $data['client_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? 0,
                'deadline_at' => $data['deadline_at'] ?? null,
                'address' => $data['address'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            $this->log->channel('audit')->info('Construction project created', [
                'project_id' => $project->id,
                'tenant_id' => $tenantId,
                'correlation_id' => $this->correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Списание материалов на проект (КАНОН 2026: атомарность)
     */
    public function deductMaterial(int $materialId, float $usage, string $reason = 'manual'): bool
    {
        return $this->db->transaction(function () use ($materialId, $usage, $reason) {
            $material = ConstructionMaterial::lockForUpdate()->findOrFail($materialId);
            
            if ($material->quantity < $usage) {
                throw new \RuntimeException("Insufficient material quantity: {$material->name}");
            }

            $material->quantity -= $usage;
            $material->actual_usage += $usage;
            $material->save();

            $this->log->channel('audit')->info('Material deducted from project', [
                'material_id' => $materialId,
                'project_id' => $material->project_id,
                'usage' => $usage,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }
}
