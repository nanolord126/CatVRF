<?php declare(strict_types=1);

namespace App\Domains\Search\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Search\Models\SearchIndex;

final class SearchIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SearchIndex $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'searchable_type' => $this->searchable_type,
            'searchable_id' => $this->searchable_id,
            'title' => $this->title,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'ranking_score' => $this->ranking_score,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
