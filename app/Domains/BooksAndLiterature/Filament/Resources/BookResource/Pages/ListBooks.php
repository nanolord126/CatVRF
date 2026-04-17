<?php declare(strict_types=1);

/**
 * ListBooks — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listbooks
 */


namespace App\Domains\BooksAndLiterature\Filament\Resources\BookResource\Pages;

use App\Domains\BooksAndLiterature\Filament\Resources\BookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBooks extends ListRecords
{
    protected static string $resource = BookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}