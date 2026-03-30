<?php declare(strict_types=1);

namespace Modules\Beauty\Http\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SalonResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function toArray($request): array
        {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'address' => $this->address,
                'phone' => $this->phone,
            ];
        }
}
