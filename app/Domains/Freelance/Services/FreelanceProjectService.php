<?php declare(strict_types=1);

namespace Modules\Freelance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Freelance\Models\FreelanceProject;

final class FreelanceProjectService
{
    public function createProject(array $data, int $clientId, string $correlationId): FreelanceProject
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createProject'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createProject', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($data, $clientId, $correlationId) {
            Log::channel('audit')->info('Creating freelance project', ['correlation_id' => $correlationId]);

            return FreelanceProject::create([
                'client_id' => $clientId,
                'title' => $data['title'],
                'description' => $data['description'],
                'budget' => $data['budget'],
                'status' => 'open',
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
