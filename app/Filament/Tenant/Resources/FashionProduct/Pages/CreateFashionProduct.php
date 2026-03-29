<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FashionProduct\Pages;
use App\Filament\Tenant\Resources\FashionProductResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordFashionProduct extends CreateRecord {
    protected static string $resource = FashionProductResource::class;
}
