<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationReactionResource\Pages;

use App\Filament\Resources\NotificationReactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditNotificationReaction extends EditRecord
{
    protected static string $resource = NotificationReactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
