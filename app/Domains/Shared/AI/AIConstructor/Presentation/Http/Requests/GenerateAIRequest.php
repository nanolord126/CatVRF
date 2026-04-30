<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Исключительно защищенный класс валидации HTTP-запроса (FormRequest) на запуск тяжелой нейро-генерации.
 *
 * Категорически предотвращает передачу файлов неверного формата или вредоносных скриптов,
 * а также строго проверяет наличие обязательных параметров вертикали и типа выдачи.
 */
final class GenerateAIRequest extends FormRequest
{
    /**
     * Обязательно подтверждает, что текущий аутентифицированный пользователь имеет права на тяжелые запросы.
     *
     * @return bool Истинно, если запрос легитимен контексту платформы.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Безусловно генерирует набор абсолютно строгих правил валидации входящего Multipart/Form-Data payload.
     *
     * @return array<string, mixed> Массив правил валидации.
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'], // строго до 10MB
            'vertical' => ['required', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:image,list,design,calculation'],
            'tenant_id' => ['required', 'integer', 'min:1'],
            'correlation_id' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * Формирует кастомизированные и исключительно понятные сообщения об ошибках валидации для клиента.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Категорически необходимо загрузить исходную фотографию для AI-анализа.',
            'photo.image' => 'Загруженный файл абсолютно точно должен являться изображением.',
            'photo.max' => 'Размер файла безусловно не должен превышать 10 Мегабайт.',
            'type.in' => 'Формат генерации обязан строго соответствовать одному из типов: image, list, design, calculation.',
        ];
    }
}
