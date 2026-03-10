<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Валидация и создание нового пользователя (Production 2026).
     * Защита от фрода, rate limiting, логирование всех действий.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $correlationId = Str::uuid()->toString();
        $clientIp = request()->ip();
        $userAgent = request()->userAgent();

        // RATE LIMITING: Защита от массовой регистрации (5 за час с одного IP)
        if (RateLimiter::tooManyAttempts("register:{$clientIp}", 5)) {
            AuditLog::create([
                'action' => 'user.registration_rate_limit',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
                'metadata' => ['reason' => 'too_many_attempts']
            ]);
            throw new \Exception('Слишком много попыток регистрации. Попробуйте позже.');
        }
        RateLimiter::hit("register:{$clientIp}", 3600);

        // ВАЛИДАЦИЯ
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Zа-яА-ЯёЁ\s\-\']+$/u'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // ПРОВЕРКА БЕЗОПАСНОСТИ ПАРОЛЯ
        if (!$this->validatePasswordSecurity($input['password'], $input['email'], $input['name'])) {
            AuditLog::create([
                'action' => 'user.registration_failed',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
                'metadata' => ['email' => $input['email'], 'reason' => 'weak_password']
            ]);
            throw new \Exception('Пароль не соответствует требованиям безопасности.');
        }

        // СОЗДАНИЕ ПОЛЬЗОВАТЕЛЯ
        $user = User::create([
            'name' => trim($input['name']),
            'email' => strtolower($input['email']),
            'password' => Hash::make($input['password']),
            'correlation_id' => $correlationId,
        ]);

        // ЛОГИРОВАНИЕ УСПЕШНОЙ РЕГИСТРАЦИИ
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.registered',
            'description' => 'Новый пользователь зарегистрирован',
            'model_type' => 'User',
            'model_id' => $user->id,
            'ip_address' => $clientIp,
            'user_agent' => $userAgent,
            'correlation_id' => $correlationId,
            'metadata' => [
                'email' => $user->email,
                'name' => $user->name,
            ]
        ]);

        return $user;
    }
}
