<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Entertainment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VerifyTicketRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function rules(): array
        {
            return array_merge(parent::rules(), [
                'ticket_id' => ['required', 'string', 'uuid'],
            ]);
        }
}
