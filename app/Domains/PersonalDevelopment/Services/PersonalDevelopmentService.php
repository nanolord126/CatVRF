<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class PersonalDevelopmentService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly PricingService $pricingService,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создание программы личного развития (курс, вебинар, коучинг).
     */
    public function createProgram(
        int $tenantId,
        string $title,
        string $description,
        string $type,
        int $priceKopecks,
        int $maxParticipants,
        string $correlationId,
    ): Program {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pd_program_create',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($tenantId, $title, $description, $type, $priceKopecks, $maxParticipants, $correlationId): Program {
            $program = Program::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'author_id' => $this->guard->id(),
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'price_kopecks' => $priceKopecks,
                'max_participants' => $maxParticipants,
                'status' => 'draft',
                'is_corporate' => false,
                'correlation_id' => $correlationId,
                'tags' => ['type' => $type],
            ]);

            $this->logger->info('PD: Program created', [
                'program_uuid' => $program->uuid,
                'title' => $title,
                'type' => $type,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return $program;
        });
    }

    /**
     * Публикация программы (из draft в active).
     */
    public function publishProgram(int $programId, string $correlationId): Program
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pd_program_publish',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($programId, $correlationId): Program {
            $program = Program::lockForUpdate()->findOrFail($programId);

            if ($program->status !== 'draft') {
                throw new \RuntimeException("Program {$programId} is not in draft status.");
            }

            $program->update([
                'status' => 'active',
                'published_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('PD: Program published', [
                'program_uuid' => $program->uuid,
                'program_id' => $programId,
                'correlation_id' => $correlationId,
            ]);

            return $program->refresh();
        });
    }

    /**
     * Обновление параметров программы.
     */
    public function updateProgram(int $programId, array $data, string $correlationId): Program
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pd_program_update',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($programId, $data, $correlationId): Program {
            $program = Program::lockForUpdate()->findOrFail($programId);

            $allowedFields = ['title', 'description', 'price_kopecks', 'max_participants', 'is_corporate'];
            $filteredData = array_intersect_key($data, array_flip($allowedFields));
            $filteredData['correlation_id'] = $correlationId;

            $program->update($filteredData);

            $this->logger->info('PD: Program updated', [
                'program_uuid' => $program->uuid,
                'updated_fields' => array_keys($filteredData),
                'correlation_id' => $correlationId,
            ]);

            return $program->refresh();
        });
    }

    /**
     * Архивирование программы (закрытие набора).
     */
    public function archiveProgram(int $programId, string $correlationId): Program
    {
        return $this->db->transaction(function () use ($programId, $correlationId): Program {
            $program = Program::lockForUpdate()->findOrFail($programId);

            $program->update([
                'status' => 'archived',
                'archived_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('PD: Program archived', [
                'program_uuid' => $program->uuid,
                'program_id' => $programId,
                'correlation_id' => $correlationId,
            ]);

            return $program->refresh();
        });
    }

    /**
     * Получение активных программ для тенанта.
     */
    public function getActivePrograms(int $tenantId): \Illuminate\Support\Collection
    {
        return Program::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Поиск программ по ключевому слову.
     */
    public function searchPrograms(string $query, int $tenantId): \Illuminate\Support\Collection
    {
        return Program::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->get();
    }
}
