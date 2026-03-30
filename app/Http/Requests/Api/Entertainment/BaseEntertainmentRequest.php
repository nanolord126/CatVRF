<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Entertainment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BaseEntertainmentRequest extends Model
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
                'correlation_id' => ['nullable', 'string', 'uuid'],
            ];
        }
}
