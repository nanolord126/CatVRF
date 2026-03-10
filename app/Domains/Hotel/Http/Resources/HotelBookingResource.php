<?php
namespace App\Domains\Hotel\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class HotelBookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'hotel' => $this->hotel_id, 'room' => $this->room_id, 'check_in' => $this->check_in, 'check_out' => $this->check_out, 'total' => $this->total_price];
    }
}
