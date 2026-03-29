<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Cosmetics\Pages;
use App\Filament\Tenant\Resources\CosmeticsResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordCosmetics extends CreateRecord {
    protected static string $resource = CosmeticsResource::class;
}
