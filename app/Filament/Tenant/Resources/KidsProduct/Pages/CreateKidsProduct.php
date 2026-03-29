<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\KidsProduct\Pages;
use App\Filament\Tenant\Resources\KidsProductResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordKidsProduct extends CreateRecord {
    protected static string $resource = KidsProductResource::class;
}
