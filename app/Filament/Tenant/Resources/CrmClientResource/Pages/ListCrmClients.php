<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmClientResource\Pages;

use App\Filament\Tenant\Resources\CrmClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

/**
 * ListCrmClients — список CRM-клиентов в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class ListCrmClients extends ListRecords
{
    protected static string $resource = CrmClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Новый клиент')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('export')
                ->label('Экспорт CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): void {
                    \App\Jobs\ExportCrmClientsJob::dispatch(
                        tenantId: filament()->getTenant()->getKey(),
                        correlationId: \Illuminate\Support\Str::uuid()->toString(),
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Экспорт запущен')
                        ->body('Файл будет готов в течение нескольких минут')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('import')
                ->label('Импорт')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('CSV файл')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    \App\Jobs\ImportCrmClientsJob::dispatch(
                        filePath: $data['file'],
                        tenantId: filament()->getTenant()->getKey(),
                        correlationId: \Illuminate\Support\Str::uuid()->toString(),
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Импорт запущен')
                        ->body('Клиенты будут загружены в течение нескольких минут')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    /**
     * Строковое представление для отладки.
     */
    public function __toString(): string
    {
        return 'ListCrmClients';
    }
}
