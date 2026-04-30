<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CooldownActionType;
use App\Services\Security\CooldownService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;

/**
 * Trigger Cooldown on Sensitive Action Listener
 * 
 * Automatically triggers cooldown periods when sensitive actions are performed.
 * Listens to various security events and applies appropriate cooldowns.
 */
final class TriggerCooldownOnSensitiveAction implements ShouldQueue
{
    public function __construct(
        private readonly CooldownService $cooldownService
    ) {}

    /**
     * Handle password changed event
     */
    public function handlePasswordChanged(object $event): void
    {
        $user = $event->user ?? Auth::user();
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::PASSWORD_CHANGE,
            hours: 48,
            reason: 'Пароль был изменён',
            metadata: [
                'triggered_by' => 'password_change_event',
            ]
        );
    }

    /**
     * Handle new device login event
     */
    public function handleNewDeviceLogin(object $event): void
    {
        $user = $event->user ?? Auth::user();
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::NEW_DEVICE,
            hours: 24,
            reason: 'Вход с нового устройства',
            metadata: [
                'device_fingerprint' => $event->deviceFingerprint ?? null,
                'ip_address' => $event->ipAddress ?? null,
            ]
        );
    }

    /**
     * Handle 2FA changed event
     */
    public function handleTwoFactorChanged(object $event): void
    {
        $user = $event->user ?? Auth::user();
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::TWO_FA_CHANGE,
            hours: 48,
            reason: 'Двухфакторная аутентификация была изменена',
            metadata: [
                'triggered_by' => '2fa_change_event',
            ]
        );
    }

    /**
     * Handle passkey changed event
     */
    public function handlePasskeyChanged(object $event): void
    {
        $user = $event->user ?? Auth::user();
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::PASSKEY_CHANGE,
            hours: 48,
            reason: 'Passkey был изменён',
            metadata: [
                'credential_id' => $event->credentialId ?? null,
            ]
        );
    }

    /**
     * Handle bank details changed event
     */
    public function handleBankDetailsChanged(object $event): void
    {
        $user = $event->user ?? Auth::user();
        $tenantId = $event->tenantId ?? $user?->tenant_id;
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::CHANGE_BANK,
            hours: 72,
            reason: 'Банковские реквизиты были изменены',
            tenantId: $tenantId,
            metadata: [
                'previous_bank' => $event->previousBank ?? null,
                'new_bank' => $event->newBank ?? null,
            ]
        );
    }

    /**
     * Handle email changed event
     */
    public function handleEmailChanged(object $event): void
    {
        $user = $event->user ?? Auth::user();
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::EMAIL_CHANGE,
            hours: 72,
            reason: 'Email был изменён',
            metadata: [
                'previous_email' => $event->previousEmail ?? null,
                'new_email' => $event->newEmail ?? null,
            ]
        );
    }

    /**
     * Handle phone changed event
     */
    public function handlePhoneChanged(object $event): void
    {
        $user = $event->user ?? Auth::user();
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::PHONE_CHANGE,
            hours: 72,
            reason: 'Телефон был изменён',
            metadata: [
                'previous_phone' => $event->previousPhone ?? null,
                'new_phone' => $event->newPhone ?? null,
            ]
        );
    }

    /**
     * Handle role changed event
     */
    public function handleRoleChanged(object $event): void
    {
        $user = $event->user ?? Auth::user();
        $tenantId = $event->tenantId ?? $user?->tenant_id;
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::ROLE_CHANGE,
            hours: 24,
            reason: 'Роль пользователя была изменена',
            tenantId: $tenantId,
            metadata: [
                'previous_role' => $event->previousRole ?? null,
                'new_role' => $event->newRole ?? null,
            ]
        );
    }

    /**
     * Handle staff invited event
     */
    public function handleStaffInvited(object $event): void
    {
        $user = $event->invitedBy ?? Auth::user();
        $tenantId = $event->tenantId ?? $user?->tenant_id;
        
        if ($user === null) {
            return;
        }

        $this->cooldownService->startCooldown(
            $user,
            CooldownActionType::STAFF_INVITE,
            hours: 24,
            reason: 'Был приглашён новый сотрудник',
            tenantId: $tenantId,
            metadata: [
                'invited_user_id' => $event->invitedUserId ?? null,
                'invited_role' => $event->invitedRole ?? null,
            ]
        );
    }
}
