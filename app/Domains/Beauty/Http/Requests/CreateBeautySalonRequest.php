<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateBeautySalonRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            return FraudControlService::check(
                userId: auth()->id() ?? 0,
                operationType: 'beauty_salon_create',
                amount: 0
            );
        }

        public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:500'],
                'description' => ['nullable', 'string'],
                'phone' => ['required', 'string', 'max:20'],
                'email' => ['nullable', 'email', 'max:255'],
                'schedule' => ['nullable', 'array'],
                'tags' => ['nullable', 'array'],
                'is_active' => ['boolean'],
            ];
        }
}
