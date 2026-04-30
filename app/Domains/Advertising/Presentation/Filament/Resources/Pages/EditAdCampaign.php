<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditAdCampaign extends EditRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
            Actions\Action::make('activate')
                ->label('Activate Campaign')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'draft' || $this->record->status === 'paused')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'active',
                        'correlation_id' => (string) Str::uuid(),
                    ]);
                    $this->notify('success', 'Campaign activated successfully');
                }),

            Actions\Action::make('pause')
                ->label('Pause Campaign')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status === 'active')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'paused',
                        'correlation_id' => (string) Str::uuid(),
                    ]);
                    $this->notify('warning', 'Campaign paused successfully');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
