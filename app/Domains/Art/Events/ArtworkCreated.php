<?php

declare(strict_types=1);

namespace App\Domains\Art\Events;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Domains\Art\Models\Artwork;

final class ArtworkCreated
{
    public function __construct(private readonly BusDispatcher $bus,
        private readonly EventDispatcher $eventDispatcher,
        public readonly Artwork $artwork,
        public readonly string $correlationId,
        public array $context = [],) {}

    public static function $this->bus->dispatch(Artwork $artwork, string $correlationId, array $context = []): void
    {
        $this->eventDispatcher->dispatch(new self($artwork, $correlationId, $context));
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
