<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Books\Pages;
use App\Filament\Tenant\Resources\BooksResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordBooks extends EditRecord {
    protected static string $resource = BooksResource::class;
}
