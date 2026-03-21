<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages;

use App\Filament\Tenant\Resources\Auto\AutoPartResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateAutoPart extends CreateRecord
{
    protected static string $resource = AutoPartResource::class;
}
