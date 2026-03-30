<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendChatMessageRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            return auth()->check();
        }

        public function rules(): array
        {
            return [
                'message' => 'required|string|min:1|max:500',
                'message_type' => 'required|in:text,gift,product,donation',
            ];
        }

        public function messages(): array
        {
            return [
                'message.required' => 'Сообщение не может быть пустым',
                'message.max' => 'Сообщение не должно превышать 500 символов',
                'message_type.required' => 'Укажите тип сообщения',
            ];
        }
}
