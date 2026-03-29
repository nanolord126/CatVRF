<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Books\Pages;
use App\Filament\Tenant\Resources\BooksResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordBooks extends CreateRecord {
    protected static string $resource = BooksResource::class;
}
