<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageReviewResource\Pages;

use App\Filament\Tenant\Resources\BeverageReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBeverageReviews extends ListRecords
{
    protected static string $resource = BeverageReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Manually Post Feedback')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}
