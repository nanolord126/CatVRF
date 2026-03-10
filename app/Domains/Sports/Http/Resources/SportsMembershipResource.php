<?php
namespace App\Domains\Sports\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class SportsMembershipResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'athlete' => $this->athlete_id, 'tier' => $this->tier, 'status' => $this->status, 'expires' => $this->expires_at];
    }
}
