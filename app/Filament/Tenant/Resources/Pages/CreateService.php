<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Service\Pages;

use use App\Filament\Tenant\Resources\ServiceResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string
    {
        return 'Create Service';
    }
}