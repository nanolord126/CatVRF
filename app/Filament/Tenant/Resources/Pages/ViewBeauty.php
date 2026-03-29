<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use use App\Filament\Tenant\Resources\BeautyResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBeauty extends ViewRecord
{
    protected static string $resource = BeautyResource::class;

    public function getTitle(): string
    {
        return 'View Beauty';
    }
}