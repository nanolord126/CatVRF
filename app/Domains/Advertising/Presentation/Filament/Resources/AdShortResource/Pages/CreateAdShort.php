<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AdShortResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdShortResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateAdShort extends CreateRecord
{
    protected static string $resource = AdShortResource::class;
}
