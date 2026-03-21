<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceJobResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceJobResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewServiceJob extends ViewRecord
{
    protected static string $resource = ServiceJobResource::class;
}
