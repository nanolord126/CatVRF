<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToysKids\Pages;

use use App\Filament\Tenant\Resources\ToysKidsResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateToysKids extends CreateRecord
{
    protected static string $resource = ToysKidsResource::class;

    public function getTitle(): string
    {
        return 'Create ToysKids';
    }
}