<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrderResource;

use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class ListToyOrders extends ListRecords
{
    protected static string $resource = \App\Filament\Tenant\Resources\ToyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
