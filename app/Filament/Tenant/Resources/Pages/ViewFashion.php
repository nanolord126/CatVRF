<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fashion\Pages;

use use App\Filament\Tenant\Resources\FashionResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFashion extends ViewRecord
{
    protected static string $resource = FashionResource::class;

    public function getTitle(): string
    {
        return 'View Fashion';
    }
}