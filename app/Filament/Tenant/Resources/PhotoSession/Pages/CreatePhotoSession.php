<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PhotoSession\Pages;
use App\Filament\Tenant\Resources\PhotoSessionResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordPhotoSession extends CreateRecord {
    protected static string $resource = PhotoSessionResource::class;
}
