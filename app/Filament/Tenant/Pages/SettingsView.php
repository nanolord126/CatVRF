<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForm;
use Illuminate\Database\DatabaseManager;

/**
 * SettingsView — настройки тенанта в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: профиль бизнеса, уведомления, интеграции, API ключи.
 * Tenant-scoped: настройки для текущего tenant.
 */
final class SettingsView extends Page implements HasForm
{
    public ?array $data = [];
    public array $settings = [];

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Настройки';
    protected static ?string $navigationGroup = 'Business';
    protected static ?string $slug = 'settings';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.tenant.pages.settings-view';

    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function mount(): void
    {
        $this->loadSettings();
        $this->form->fill($this->settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('company_name')
                    ->label('Название компании')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel(),
                Textarea::make('address')
                    ->label('Адрес')
                    ->rows(3),
                TextInput::make('timezone')
                    ->label('Часовой пояс')
                    ->default('Europe/Moscow'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $tenantId = tenant()?->id;
        if (!$tenantId) {
            return;
        }

        $this->db->table('tenants')
            ->where('id', $tenantId)
            ->update([
                'name' => $this->data['company_name'] ?? null,
                'email' => $this->data['email'] ?? null,
                'phone' => $this->data['phone'] ?? null,
                'address' => $this->data['address'] ?? null,
                'timezone' => $this->data['timezone'] ?? 'Europe/Moscow',
                'updated_at' => now(),
            ]);

        $this->loadSettings();
        $this->form->fill($this->settings);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Сохранить')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    private function loadSettings(): void
    {
        $tenantId = tenant()?->id;
        if (!$tenantId) {
            return;
        }

        $tenant = $this->db->table('tenants')
            ->where('id', $tenantId)
            ->first();

        $this->settings = [
            'company_name' => $tenant->name ?? '',
            'email' => $tenant->email ?? '',
            'phone' => $tenant->phone ?? '',
            'address' => $tenant->address ?? '',
            'timezone' => $tenant->timezone ?? 'Europe/Moscow',
        ];
    }
}
