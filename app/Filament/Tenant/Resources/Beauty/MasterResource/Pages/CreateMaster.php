<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\MasterResource\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateMaster extends CreateRecord
{
    protected static string $resource = MasterResource::class;
}
