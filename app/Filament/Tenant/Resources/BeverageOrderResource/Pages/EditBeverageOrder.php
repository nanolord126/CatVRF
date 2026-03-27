<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageOrderResource\Pages;

use App\Filament\Tenant\Resources\BeverageOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

final class EditBeverageOrder extends EditRecord
{
    protected static string $resource = BeverageOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Beverage Order Status Updated', [
            'order_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'status' => $this->record->status,
            'user_id' => auth()->id(),
        ]);
    }
}
