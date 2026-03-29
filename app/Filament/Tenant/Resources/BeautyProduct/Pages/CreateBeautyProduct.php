<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeautyProduct\Pages;
use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordBeautyProduct extends CreateRecord {
    protected static string $resource = BeautyProductResource::class;
}
