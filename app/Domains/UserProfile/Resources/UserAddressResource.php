<?php declare(strict_types=1);

namespace App\Domains\UserProfile\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\UserProfile\Models\UserAddress;

final class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var UserAddress $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'is_default' => $this->is_default,
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
