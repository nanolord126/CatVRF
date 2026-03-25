declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * SendChatMessageRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SendChatMessageRequest extends FormRequest
{
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
