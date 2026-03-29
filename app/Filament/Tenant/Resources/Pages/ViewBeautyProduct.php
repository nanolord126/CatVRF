<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyProduct\Pages;

use use App\Filament\Tenant\Resources\BeautyProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBeautyProduct extends ViewRecord
{
    protected static string $resource = BeautyProductResource::class;

    public function getTitle(): string
    {
        return 'View BeautyProduct';
    }
}