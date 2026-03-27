<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageReviewResource\Pages;

use App\Filament\Tenant\Resources\BeverageReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

final class EditBeverageReview extends EditRecord
{
    protected static string $resource = BeverageReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Beverage Review Moderation Status Updated', [
            'review_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }
}
