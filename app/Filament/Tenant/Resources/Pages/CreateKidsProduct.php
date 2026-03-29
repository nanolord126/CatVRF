<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsProduct\Pages;

use use App\Filament\Tenant\Resources\KidsProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateKidsProduct extends CreateRecord
{
    protected static string $resource = KidsProductResource::class;

    public function getTitle(): string
    {
        return 'Create KidsProduct';
    }
}