<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet\Pages;

use App\Filament\Tenant\Resources\Pet\PetServiceResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateService extends CreateRecord
{
    protected static string $resource = PetServiceResource::class;
}
