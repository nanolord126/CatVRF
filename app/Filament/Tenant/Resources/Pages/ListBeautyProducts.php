<?php

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBeautyProducts extends ListRecords
{
    protected static string $resource = BeautyProductResource::class;
}
