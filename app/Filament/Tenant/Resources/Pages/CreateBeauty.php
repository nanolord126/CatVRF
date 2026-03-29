<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use use App\Filament\Tenant\Resources\BeautyResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateBeauty extends CreateRecord
{
    protected static string $resource = BeautyResource::class;

    public function getTitle(): string
    {
        return 'Create Beauty';
    }
}