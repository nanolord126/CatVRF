<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BookOrder\Pages;
use App\Filament\Tenant\Resources\BookOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordBookOrder extends CreateRecord {
    protected static string $resource = BookOrderResource::class;
}
