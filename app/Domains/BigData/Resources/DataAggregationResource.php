<?php declare(strict_types=1);

namespace App\Domains\BigData\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\BigData\Models\DataAggregation;

final class DataAggregationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DataAggregation $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'source' => $this->source,
            'aggregation_type' => $this->aggregation_type,
            'aggregation_key' => $this->aggregation_key,
            'value' => $this->value,
            'timestamp' => $this->timestamp?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
