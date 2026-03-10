<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Валидация и обновление пароля (Production 2026).
     * Проверка текущего пароля, защита от переиспользования, rate limiting.
     *
     * @param  User  $user
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        $correlationId = Str::uuid()->toString();
        $clientIp = request()->ip();
        $userAgent = request()->userAgent();

        // RATE LIMITING: 5 попыток в час
        if (RateLimiter::tooManyAttempts("password_update:{$user->id}:{$clientIp}", 5)) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.password_update_rate_limit',
                'description' => 'Превышен лимит попыток изменения пароля',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
            ]);
            throw new \Exception('Слишком много попыток изменения пароля.');
        }
        RateLimiter::hit("password_update:{$user->id}:{$clientIp}", 3600);

        // ВАЛИДАЦИЯ
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => 'Введённый пароль не совпадает с текущим.',
        ])->validateWithBag('updatePassword');

        // ПРОВЕРКА БЕЗОПАСНОСТИ НОВОГО ПАРОЛЯ
        if (!$this->validatePasswordSecurity($input['password'], $user->email, $user->name)) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.password_update_failed',
                'description' => 'Слабый пароль',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
                'metadata' => ['reason' => 'weak_password']
            ]);
            throw new \Exception('Новый пароль не соответствует требованиям безопасности.');
        }

        // ПРОВЕРКА: Новый пароль отличается от текущего
        if (Hash::check($input['password'], $user->password)) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.password_update_failed',
                'description' => 'Новый пароль совпадает с текущим',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
            ]);
            throw new \Exception('Новый пароль должен отличаться от текущего.');
        }

        // ОБНОВЛЕНИЕ ПАРОЛЯ
        $user->forceFill([
            'password' => Hash::make($input['password']),
            'password_changed_at' => now(),
        ])->save();

        // ЛОГИРОВАНИЕ УСПЕШНОГО ОБНОВЛЕНИЯ
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.password_updated',
            'description' => 'Пароль успешно обновлён',
            'model_type' => 'User',
            'model_id' => $user->id,
            'ip_address' => $clientIp,
            'user_agent' => $userAgent,
            'correlation_id' => $correlationId,
            'metadata' => [
                'email' => $user->email,
                'session_id' => session()->getId(),
                'password_strength' => $this->calculatePasswordStrength($input['password'])
            ]
        ]);
    }

    /**
     * Оценить надёжность пароля (1-5 звёзд).
     */
    private function calculatePasswordStrength(string $password): int
    {
        $strength = 0;
        if (strlen($password) >= 12) $strength++;
        if (strlen($password) >= 16) $strength++;
        if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) $strength++;
        if (preg_match('/[0-9]/', $password)) $strength++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength++;
        return min(5, $strength);
    }
}
