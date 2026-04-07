<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Construction\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ConstructionService
{

    private string $correlationId;

        public function __construct(?string $correlationId = null,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard)
        {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        /**
         * Создание нового строительного проекта
         */
        public function createProject(array $data, int $tenantId): ConstructionProject
        {
            // 1. Fraud Check (защита от массового создания фейковых строек)
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

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

                $this->logger->info('Construction project created', [
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

                $this->logger->info('Material deducted from project', [
                    'material_id' => $materialId,
                    'project_id' => $material->project_id,
                    'usage' => $usage,
                    'correlation_id' => $this->correlationId,
                ]);

                return true;
            });
        }
}
