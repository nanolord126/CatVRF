<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Entertainment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookSeatRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function rules(): array
        {
            return array_merge(parent::rules(), [
                'event_id' => ['required', 'integer', 'exists:entertainment_events,id'],
                'seats' => ['required', 'array', 'min:1'],
                'seats.*.row' => ['required', 'integer'],
                'seats.*.col' => ['required', 'integer'],
            ]);
        }
}
