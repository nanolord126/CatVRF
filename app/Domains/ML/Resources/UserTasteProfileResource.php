<?php declare(strict_types=1);

namespace App\Domains\ML\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\ML\Models\UserTasteProfile;

final class UserTasteProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var UserTasteProfile $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'category_preferences' => $this->category_preferences,
            'price_range' => $this->price_range,
            'brand_affinities' => $this->brand_affinities,
            'behavioral_score' => $this->behavioral_score,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
