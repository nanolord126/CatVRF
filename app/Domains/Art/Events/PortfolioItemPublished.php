<?php
declare(strict_types=1);

namespace App\Domains\Art\Events;

use App\Domains\Art\Models\PortfolioItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class PortfolioItemPublished
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly PortfolioItem $item,
        public readonly string $correlationId,
        public readonly array $context = [],
    ) {
        Log::channel('audit')->info('PortfolioItemPublished event dispatched', [
            'correlation_id' => $this->correlationId,
            'portfolio_item_id' => $this->item->id,
            'project_id' => $this->item->project_id,
            'artist_id' => $this->item->artist_id,
            'tenant_id' => $this->item->tenant_id,
            'published_at' => $this->item->published_at,
        ]);
    }

    public static function dispatch(PortfolioItem $item, string $correlationId, array $context = []): void
    {
        event(new self($item, $correlationId, $context));
    }

    public function decisionPayload(): array
    {
        return [
            'portfolio_item_id' => $this->item->id,
            'project_id' => $this->item->project_id,
            'artist_id' => $this->item->artist_id,
            'tenant_id' => $this->item->tenant_id,
            'business_group_id' => $this->item->business_group_id,
            'published_at' => $this->item->published_at,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function auditContext(): array
    {
        return array_merge($this->decisionPayload(), [
            'title' => $this->item->title,
            'cover_url' => $this->item->cover_url,
            'tags' => $this->item->tags,
            'meta' => $this->item->meta,
            'context' => $this->context,
        ]);
    }

    public function isPublished(): bool
    {
        return $this->item->published_at !== null;
    }

    public function describe(): string
    {
        return sprintf(
            'Portfolio item %s for artist %s (tenant %s)',
            $this->item->title,
            $this->item->artist_id,
            $this->item->tenant_id,
        );
    }
}
