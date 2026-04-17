<?php declare(strict_types=1);

namespace App\Domains\UserProfile\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\UserProfile\Models\UserProfile;

final class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var UserProfile $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullName(),
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->getAge(),
            'gender' => $this->gender,
            'preferred_language' => $this->preferred_language,
            'timezone' => $this->timezone,
            'bio' => $this->bio,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
