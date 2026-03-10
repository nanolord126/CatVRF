<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Валидация и сброс забытого пароля (Production 2026).
     * Защита от фрода, rate limiting, проверка безопасности пароля.
     *
     * @param  User  $user
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        $correlationId = Str::uuid()->toString();
        $clientIp = request()->ip();
        $userAgent = request()->userAgent();

        // RATE LIMITING: 3 попытки в час
        if (RateLimiter::tooManyAttempts("password_reset:{$user->id}:{$clientIp}", 3)) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.password_reset_rate_limit',
                'description' => 'Превышен лимит попыток сброса пароля',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
                'metadata' => ['reason' => 'too_many_attempts']
            ]);
            throw new \Exception('Слишком много попыток сброса пароля. Попробуйте позже.');
        }
        RateLimiter::hit("password_reset:{$user->id}:{$clientIp}", 3600);

        // ВАЛИДАЦИЯ
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        // ПРОВЕРКА БЕЗОПАСНОСТИ ПАРОЛЯ
        if (!$this->validatePasswordSecurity($input['password'], $user->email, $user->name)) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.password_reset_failed',
                'description' => 'Слабый пароль',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
                'metadata' => ['reason' => 'weak_password']
            ]);
            throw new \Exception('Пароль не соответствует требованиям безопасности.');
        }

        // ОБНОВЛЕНИЕ ПАРОЛЯ
        $user->forceFill([
            'password' => Hash::make($input['password']),
            'password_changed_at' => now(),
        ])->save();

        // ЛОГИРОВАНИЕ СБРОСА ПАРОЛЯ
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.password_reset',
            'description' => 'Пароль успешно сброшен',
            'model_type' => 'User',
            'model_id' => $user->id,
            'ip_address' => $clientIp,
            'user_agent' => $userAgent,
            'correlation_id' => $correlationId,
            'metadata' => [
                'email' => $user->email,
                'password_age_days' => now()->diffInDays($user->password_changed_at ?? $user->created_at)
            ]
        ]);
    }
}
