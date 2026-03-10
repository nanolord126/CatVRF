<?php
namespace App\Domains\Food\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class FoodOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'status' => $this->status, 'total' => $this->total_amount, 'items' => $this->items_count, 'created_at' => $this->created_at];
    }
}
