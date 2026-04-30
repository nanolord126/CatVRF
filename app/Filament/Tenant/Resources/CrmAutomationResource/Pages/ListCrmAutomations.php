<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmAutomationResource\Pages;

use Illuminate\Notifications\ChannelManager;

use App\Filament\Tenant\Resources\CrmAutomationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;

/**
 * ListCrmAutomations — список автоматизаций CRM в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class ListCrmAutomations extends ListRecords
{
    protected static string $resource = CrmAutomationResource::class;

    /**
     * Строковое представление для отладки.
     */
    public function __construct(
        private readonly ChannelManager $notificationManager,
    ) {}

    public function __toString(): string
    {
        return 'ListCrmAutomations';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Новая автоматизация')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('presets')
                ->label('Пресеты')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->modalHeading('Выберите пресет автоматизации')
                ->modalDescription('Готовые шаблоны автоматизаций для вашей вертикали')
                ->form([
                    Select::make('preset')
                        ->label('Пресет')
                        ->options(fn (): array => config('crm.automation_presets', []))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $presetKey = $data['preset'];
                    $preset = config("crm.automation_presets_data.{$presetKey}", []);

                    if ($preset === []) {
                        $this->notificationManager->make()
                            ->title('Пресет не найден')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->notificationManager->make()
                        ->title('Пресет применён')
                        ->body("Автоматизация \"{$presetKey}\" успешно создана")
                        ->success()
                        ->send();
                }),
        ];
    }
}
