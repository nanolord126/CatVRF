<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Stream\Pages;
use App\Filament\Tenant\Resources\StreamResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordStream extends CreateRecord {
    protected static string $resource = StreamResource::class;
}
