<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelReviewResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelanceReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFreelanceReviews extends ListRecords
{
    protected static string $resource = FreelanceReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
