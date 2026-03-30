<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TuningProjectCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
