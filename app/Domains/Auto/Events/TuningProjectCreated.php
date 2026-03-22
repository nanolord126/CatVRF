<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\TuningProject;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class TuningProjectCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TuningProject $project,
        public readonly string $correlationId
    ) {
        Log::channel('audit')->info('TuningProjectCreated event dispatched', [
            'correlation_id' => $this->correlationId,
            'project_id' => $this->project->id,
            'type' => $this->project->type,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->project->tenant_id),
            new PrivateChannel('user.' . $this->project->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tuning.project.created';
    }
}
