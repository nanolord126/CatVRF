<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageReviewResource\Pages;

use App\Filament\Tenant\Resources\BeverageReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateBeverageReview extends CreateRecord
{
    protected static string $resource = BeverageReviewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Beverage Review Manual Entry Recorded', [
            'review_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }
}
