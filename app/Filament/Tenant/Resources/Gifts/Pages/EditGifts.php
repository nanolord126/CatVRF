<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Gifts\Pages;
use App\Filament\Tenant\Resources\GiftsResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordGifts extends EditRecord {
    protected static string $resource = GiftsResource::class;
}
