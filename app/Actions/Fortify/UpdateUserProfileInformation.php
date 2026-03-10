<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Валидация и обновление профиля (Production 2026).
     * Защита от спама, проверка email, логирование изменений.
     *
     * @param  User  $user
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        $correlationId = Str::uuid()->toString();
        $clientIp = request()->ip();
        $userAgent = request()->userAgent();

        // RATE LIMITING: 10 обновлений в час
        if (RateLimiter::tooManyAttempts("profile_update:{$user->id}:{$clientIp}", 10)) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.profile_update_rate_limit',
                'description' => 'Превышен лимит обновлений профиля',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
            ]);
            throw new \Exception('Слишком частые обновления профиля. Попробуйте позже.');
        }
        RateLimiter::hit("profile_update:{$user->id}:{$clientIp}", 3600);

        // ВАЛИДАЦИЯ
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Zа-яА-ЯёЁ\s\-\']+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ])->validateWithBag('updateProfileInformation');

        // ЛОГИРОВАНИЕ ИЗМЕНЕНИЯ EMAIL
        $emailChanged = $input['email'] !== $user->email;
        if ($emailChanged) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user.email_change_initiated',
                'description' => 'Инициирована смена email адреса',
                'ip_address' => $clientIp,
                'correlation_id' => $correlationId,
                'metadata' => [
                    'old_email' => $user->email,
                    'new_email' => $input['email'],
                    'requires_verification' => true
                ]
            ]);
        }

        // ОБНОВЛЕНИЕ ПРОФИЛЯ
        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => trim($input['name']),
                'email' => strtolower($input['email']),
            ])->save();
        }

        // ЛОГИРОВАНИЕ УСПЕШНОГО ОБНОВЛЕНИЯ
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.profile_updated',
            'description' => 'Профиль пользователя обновлён',
            'model_type' => 'User',
            'model_id' => $user->id,
            'ip_address' => $clientIp,
            'user_agent' => $userAgent,
            'correlation_id' => $correlationId,
            'metadata' => [
                'email_changed' => $emailChanged,
                'new_email' => $user->email,
            ]
        ]);
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  User  $user
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => trim($input['name']),
            'email' => strtolower($input['email']),
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
