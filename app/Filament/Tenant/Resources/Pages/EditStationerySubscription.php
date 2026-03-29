<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationerySubscription\Pages;

use use App\Filament\Tenant\Resources\StationerySubscriptionResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStationerySubscription extends EditRecord
{
    protected static string $resource = StationerySubscriptionResource::class;

    public function getTitle(): string
    {
        return 'Edit StationerySubscription';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}