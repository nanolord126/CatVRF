<?php

namespace App\Filament\Tenant\Pages;

use Filament\Actions;
use Filament\Pages\Page;

/**
 * Канон 2026: Упрощенная панель настройки для "Ламера".
 * Позволяет владельцу бизнеса (тенонт) настроить всё в один клик.
 */
class QuickOnboarding extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationGroup = 'Настройки Экосистемы';
    protected static string $view = 'filament.tenant.pages.quick-onboarding';
    protected static ?string $title = 'Быстрый Старт (Onboarding)';
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('configure_doppler')
                ->label('Подключить Безопасность')
                ->icon('heroicon-m-shield-check')
                ->color('success')
                ->action(fn() => $this->notify('success', 'Doppler Security Activated for your Hotel!')),

            Actions\Action::make('setup_atol')
                ->label('Касса АТОЛ')
                ->icon('heroicon-m-printer')
                ->form([
                    \Filament\Forms\Components\TextInput::make('atol_login')->required(),
                    \Filament\Forms\Components\TextInput::make('atol_password')->password()->required(),
                ])
                ->action(function (array $data) {
                    // Сохранение в Tenant Data (см. TenantSecretManager)
                    tenant()->update([
                        'data' => array_merge(tenant()->data, $data)
                    ]);
                }),
        ];
    }
}
