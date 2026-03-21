<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HealthyFoodResource\Pages;

use App\Filament\Tenant\Resources\HealthyFoodResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateHealthyFood extends CreateRecord
{
    protected static string $resource = HealthyFoodResource::class;
}
