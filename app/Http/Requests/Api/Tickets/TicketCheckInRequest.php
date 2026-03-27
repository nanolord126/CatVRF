<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Tickets;

/**
 * КАНОН 2026: Валидация чекина по QR.
 */
final class TicketCheckInRequest extends BaseApiRequest
{
    /**
     * Валидация на права проверяющего.
     */
    public function authorize(): bool
    {
        // Проверка через Policy (view_admin_panel) или роль сотрудника
        return auth()->check() && (auth()->user()->role === 'checker' || auth()->user()->role === 'admin');
    }

    /**
     * Правила валидации.
     */
    public function rules(): array
    {
        return [
            'qr_code' => 'required|string|min:16|max:64',
            'location' => 'nullable|array',
            'location.lat' => 'required_with:location|numeric',
            'location.lon' => 'required_with:location|numeric',
            'device' => 'nullable|array',
            'device.id' => 'required_with:device|string',
            'device.name' => 'required_with:device|string',
        ];
    }

    /**
     * Человекочитаемые сообщения.
     */
    public function messages(): array
    {
        return [
            'qr_code.required' => 'QR код обязателен для считывания',
            'qr_code.min' => 'Неверный формат QR кода (слишком короткий)',
            'location.lat.required_with' => 'Для фиксации места необходимо передать широту(lat)',
            'location.lon.required_with' => 'Для фиксации места необходимо передать долготу(lon)',
        ];
    }
}
