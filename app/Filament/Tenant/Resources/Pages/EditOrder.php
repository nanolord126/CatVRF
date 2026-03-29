<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Order\Pages;

use use App\Filament\Tenant\Resources\OrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Edit Order';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}