<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Photographer\Pages;
use App\Filament\Tenant\Resources\PhotographerResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordPhotographer extends CreateRecord {
    protected static string $resource = PhotographerResource::class;
}
