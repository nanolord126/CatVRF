<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\ReviewResource\Pages;

use App\Filament\Tenant\Resources\Beauty\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
