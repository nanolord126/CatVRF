<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Stream\Pages;

use use App\Filament\Tenant\Resources\StreamResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateStream extends CreateRecord
{
    protected static string $resource = StreamResource::class;

    public function getTitle(): string
    {
        return 'Create Stream';
    }
}