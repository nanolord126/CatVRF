<?php

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBeautyProduct extends CreateRecord
{
    protected static string $resource = BeautyProductResource::class;
}
