<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationReactionResource\Pages;

use App\Filament\Resources\NotificationReactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateNotificationReaction extends CreateRecord
{
    protected static string $resource = NotificationReactionResource::class;
}
