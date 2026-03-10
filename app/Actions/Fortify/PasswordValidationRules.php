<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Производственные правила валидации пароля с требованиями безопасности 2026.
     * Защита от словарей и радужных таблиц.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(2),
        ];
    }

    /**
     * Дополнительная валидация пароля на соответствие требованиям безопасности.
     */
    protected function validatePasswordSecurity(string $password, ?string $email = null, ?string $name = null): bool
    {
        if (strlen($password) < 12) return false;
        
        $commonPasswords = [
            'password', 'password123', '123456', '12345678', 'qwerty', 'abc123',
            'letmein', 'welcome', 'monkey', 'admin', 'root', 'master'
        ];
        
        if (in_array(strtolower($password), $commonPasswords)) {
            return false;
        }
        
        if ($email && stripos($password, explode('@', $email)[0]) !== false) {
            return false;
        }
        
        if ($name && stripos($password, $name) !== false) {
            return false;
        }
        
        return true;
    }
}
