<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageSubscription\Pages;

use use App\Filament\Tenant\Resources\BeverageSubscriptionResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBeverageSubscription extends EditRecord
{
    protected static string $resource = BeverageSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'Edit BeverageSubscription';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}