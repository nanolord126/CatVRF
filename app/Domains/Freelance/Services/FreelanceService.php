<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use Illuminate\Support\Facades\DB;

use App\Domains\Freelance\Models\FreelanceJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class FreelanceService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function postJob(array $data): FreelanceJob
    {
        $job = FreelanceJob::create([
            'tenant_id' => auth()->user()->tenant_id,
            'uuid' => Str::uuid(),
            'correlation_id' => $this->correlationId,
            'title' => $data['title'],
            'description' => $data['description'],
            'budget' => $data['budget'],
            'deadline' => $data['deadline'],
            'status' => 'open',
        ]);

        Log::channel('audit')->info('Freelance job posted', [
            'correlation_id' => $this->correlationId,
            'job_id' => $job->id,
        ]);

        return $job;
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}