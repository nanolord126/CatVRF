<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\JewelryProduct\Pages;
use App\Filament\Tenant\Resources\JewelryProductResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordJewelryProduct extends EditRecord {
    protected static string $resource = JewelryProductResource::class;
}
