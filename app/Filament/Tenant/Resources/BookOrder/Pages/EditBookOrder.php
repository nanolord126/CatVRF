<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BookOrder\Pages;
use App\Filament\Tenant\Resources\BookOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordBookOrder extends EditRecord {
    protected static string $resource = BookOrderResource::class;
}
