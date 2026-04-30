<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources\ExoticGroomingSessionResource\Pages;

use Modules\VetGrooming\Filament\Resources\ExoticGroomingSessionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateExoticGroomingSession extends CreateRecord
{
    protected static string $resource = ExoticGroomingSessionResource::class;
}
