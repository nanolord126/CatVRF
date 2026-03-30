<?php
declare(strict_types=1);

namespace App\Domains\Art\Events;

use App\Domains\Art\Models\Artwork;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ArtworkCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Artwork $artwork,
        public readonly string $correlationId,
        public readonly array $context = [],
    ) {
        Log::channel('audit')->info('ArtworkCreated event dispatched', [
            'correlation_id' => $this->correlationId,
            'artwork_id' => $this->artwork->id,
            'project_id' => $this->artwork->project_id,
            'tenant_id' => $this->artwork->tenant_id,
        ]);
    }

    public static function dispatch(Artwork $artwork, string $correlationId, array $context = []): void
    {
        event(new self($artwork, $correlationId, $context));
    }

    public function decisionPayload(): array
    {
        return [
            'artwork_id' => $this->artwork->id,
            'project_id' => $this->artwork->project_id,
            'artist_id' => $this->artwork->artist_id,
            'tenant_id' => $this->artwork->tenant_id,
            'business_group_id' => $this->artwork->business_group_id,
            'is_visible' => $this->artwork->is_visible,
            'price_cents' => $this->artwork->price_cents,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function auditContext(): array
    {
        return array_merge($this->decisionPayload(), [
            'title' => $this->artwork->title,
            'delivered_at' => $this->artwork->delivered_at,
            'tags' => $this->artwork->tags,
            'meta' => $this->artwork->meta,
            'context' => $this->context,
        ]);
    }

    public function isVisible(): bool
    {
        return $this->artwork->is_visible === true;
    }

    public function describe(): string
    {
        return sprintf(
            'Artwork %s for project %s (tenant %s)',
            $this->artwork->title,
            $this->artwork->project_id ?: 'n/a',
            $this->artwork->tenant_id,
        );
    }
}
