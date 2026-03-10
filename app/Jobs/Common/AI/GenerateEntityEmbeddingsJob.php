<?php

namespace App\Jobs\Common\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use App\Services\Common\AI\RecommendationService;
use Illuminate\Support\Facades\Redis;
use Typesense\Client as TypesenseClient;

/**
 * 2026 AI Infrastructure: Async Embedding Generation and Search Vector Update.
 */
class GenerateEntityEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Model $entity;

    public function __construct(Model $entity)
    {
        $this->entity = $entity;
    }

    public function handle(RecommendationService $recService, TypesenseClient $typesense)
    {
        // 1. Context extraction (Name, Category, Description, Metadata)
        $textToEmbed = $this->extractEmbeddableText();

        // 2. Generate vector from OpenAI text-embedding-3-large
        $vector = $recService->getEmbeddings($textToEmbed);

        // 3. Cache for quick 'similarTo' lookup in Redis
        Redis::set("vector:{$this->entity->getTable()}:{$this->entity->id}", $vector);

        // 4. Update Typesense Search Index
        $typesense->collections['marketplace_entities']->documents[$this->entity->id]->update([
            'id' => (string) $this->entity->id,
            'name' => $this->entity->name ?? '',
            'description' => $this->entity->description ?? '',
            'category' => $this->entity->category ?? 'uncategorized',
            'tenant_id' => (int) $this->entity->tenant_id,
            'embeddings' => explode(',', $vector), // Vector storage
            'location' => [
                $this->entity->lat ?? 0.0,
                $this->entity->lng ?? 0.0,
            ],
            'is_active' => $this->entity->is_active ?? true,
        ]);
    }

    private function extractEmbeddableText(): string
    {
        return implode(' ', [
            $this->entity->name,
            $this->entity->description,
            $this->entity->category ?? '',
            $this->entity->tags ?? '',
        ]);
    }
}
