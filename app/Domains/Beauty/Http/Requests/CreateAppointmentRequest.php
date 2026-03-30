<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateAppointmentRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            return true;
        }

        public function rules(): array
        {
            return [
                'salon_id' => ['required', 'exists:beauty_salons,id'],
                'master_id' => ['required', 'exists:masters,id'],
                'service_id' => ['required', 'exists:beauty_services,id'],
                'user_id' => ['nullable', 'exists:users,id'],
                'datetime_start' => ['required', 'date', 'after:now'],
                'datetime_end' => ['required', 'date', 'after:datetime_start'],
                'price' => ['required', 'integer', 'min:0'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'tags' => ['nullable', 'array'],
            ];
        }
}
