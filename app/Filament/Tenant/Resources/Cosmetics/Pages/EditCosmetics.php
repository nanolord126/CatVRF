<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Cosmetics\Pages;
use App\Filament\Tenant\Resources\CosmeticsResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordCosmetics extends EditRecord {
    protected static string $resource = CosmeticsResource::class;
}
