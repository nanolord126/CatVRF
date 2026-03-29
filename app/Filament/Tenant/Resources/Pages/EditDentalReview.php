<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalReview\Pages;

use use App\Filament\Tenant\Resources\DentalReviewResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditDentalReview extends EditRecord
{
    protected static string $resource = DentalReviewResource::class;

    public function getTitle(): string
    {
        return 'Edit DentalReview';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}