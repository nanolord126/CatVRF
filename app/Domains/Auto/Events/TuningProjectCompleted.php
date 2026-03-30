<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TuningProjectCompleted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly TuningProject $project,
            public readonly string $correlationId
        ) {
            Log::channel('audit')->info('TuningProjectCompleted event dispatched', [
                'correlation_id' => $this->correlationId,
                'project_id' => $this->project->id,
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
            return 'tuning.project.completed';
        }
}
