<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\LiveStreamResource\Pages;

use App\Domains\Sports\Filament\Resources\LiveStreamResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLiveStream extends CreateRecord
{
    protected static string $resource = LiveStreamResource::class;
}
