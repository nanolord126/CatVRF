<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CollectibleItem\Pages;
use App\Filament\Tenant\Resources\CollectibleItemResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordCollectibleItem extends CreateRecord {
    protected static string $resource = CollectibleItemResource::class;
}
