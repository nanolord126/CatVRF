<?php declare(strict_types=1);

namespace Modules\Freelance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Freelance\Models\FreelanceProject;
use App\Services\FraudControlService;

final class FreelanceProjectService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createProject(array $data, int $clientId, string $correlationId): FreelanceProject
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $clientId, $correlationId) {
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
