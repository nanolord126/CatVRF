<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsCenter\Pages;

use use App\Filament\Tenant\Resources\KidsCenterResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateKidsCenter extends CreateRecord
{
    protected static string $resource = KidsCenterResource::class;

    public function getTitle(): string
    {
        return 'Create KidsCenter';
    }
}