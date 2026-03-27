<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFreelanceOrders extends ListRecords
{
    protected static string $resource = FreelanceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
