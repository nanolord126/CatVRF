<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeautyProduct\Pages;
use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordBeautyProduct extends EditRecord {
    protected static string $resource = BeautyProductResource::class;
}
