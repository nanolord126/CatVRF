<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageReview\Pages;

use use App\Filament\Tenant\Resources\BeverageReviewResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBeverageReview extends EditRecord
{
    protected static string $resource = BeverageReviewResource::class;

    public function getTitle(): string
    {
        return 'Edit BeverageReview';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}