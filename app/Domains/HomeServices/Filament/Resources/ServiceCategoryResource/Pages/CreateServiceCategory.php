<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateServiceCategory extends CreateRecord
{
    protected static string $resource = ServiceCategoryResource::class;
}
