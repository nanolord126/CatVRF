<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\Pages;

use use App\Filament\Tenant\Resources\FlowersResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFlowers extends CreateRecord
{
    protected static string $resource = FlowersResource::class;

    public function getTitle(): string
    {
        return 'Create Flowers';
    }
}