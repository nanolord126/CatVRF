<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Book\Pages;
use App\Filament\Tenant\Resources\BookResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordBook extends CreateRecord {
    protected static string $resource = BookResource::class;
}
