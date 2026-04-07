<?php
declare(strict_types=1);

namespace App\Domains\Art\Events;


use App\Domains\Art\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
final class ProjectCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly string $correlationId,
        public array $context = [],
    ) {}

    public static function dispatch(Project $project, string $correlationId, array $context = []): void
    {
        event(new self($project, $correlationId, $context));
    }

    public function decisionPayload(): array
    {
        return [
            'project_id' => $this->project->id,
            'artist_id' => $this->project->artist_id,
            'tenant_id' => $this->project->tenant_id,
            'business_group_id' => $this->project->business_group_id,
            'correlation_id' => $this->correlationId,
            'status' => $this->project->status,
            'mode' => $this->project->mode,
        ];
    }

    public function auditContext(): array
    {
        return array_merge($this->decisionPayload(), [
            'title' => $this->project->title,
            'budget_cents' => $this->project->budget_cents,
            'created_at' => $this->project->created_at,
            'correlation_id' => $this->correlationId,
            'context' => $this->context,
        ]);
    }

    public function isB2B(): bool
    {
        return $this->project->mode === 'b2b';
    }

    public function isDraft(): bool
    {
        return $this->project->status === 'draft';
    }

    public function describe(): string
    {
        $mode = $this->isB2B() ? 'B2B' : 'B2C';

        return sprintf(
            'Project %s (%s) for artist %d in tenant %d',
            $this->project->title,
            $mode,
            $this->project->artist_id,
            $this->project->tenant_id,
        );
    }
}
