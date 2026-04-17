<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\ReviewResource\Pages;

use App\Domains\Sports\Filament\Resources\ReviewResource;
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
