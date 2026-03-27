<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceReviewResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelanceReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditFreelanceReview extends EditRecord
{
    protected static string $resource = FreelanceReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
