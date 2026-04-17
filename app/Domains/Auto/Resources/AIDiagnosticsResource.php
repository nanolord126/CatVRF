<?php declare(strict_types=1);

namespace App\Domains\Auto\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AIDiagnosticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource['success'] ?? false,
            'vehicle' => $this->resource['vehicle'] ?? [],
            'vision_analysis' => $this->resource['vision_analysis'] ?? [],
            'damage_detection' => $this->resource['damage_detection'] ?? [],
            'work_list' => $this->resource['work_list'] ?? [],
            'parts_recommendation' => $this->resource['parts_recommendation'] ?? [],
            'price_estimate' => $this->resource['price_estimate'] ?? [],
            'nearest_services' => $this->resource['nearest_services'] ?? [],
            'ar_preview_url' => $this->resource['ar_preview_url'] ?? null,
            'video_inspection_available' => $this->resource['video_inspection_available'] ?? false,
            'correlation_id' => $this->resource['correlation_id'] ?? null,
        ];
    }
}
