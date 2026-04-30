<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource;

final class ListOrderKitchenStatuses extends ListRecords
{
    protected static string $resource = OrderKitchenStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Pages\Actions\CreateAction::make(),
        ];
    }
}
