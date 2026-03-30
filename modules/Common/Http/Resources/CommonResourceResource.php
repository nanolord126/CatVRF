<?php declare(strict_types=1);

namespace Modules\Common\Http\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CommonResourceResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function toArray(Request $request): array
        {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->type,
                'data' => $this->data ? json_decode($this->data, true) : null,
                'status' => $this->status,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ];
        }
}
