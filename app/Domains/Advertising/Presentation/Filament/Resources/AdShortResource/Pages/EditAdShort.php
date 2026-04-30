<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AdShortResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdShortResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditAdShort extends EditRecord
{
    protected static string $resource = AdShortResource::class;
}
