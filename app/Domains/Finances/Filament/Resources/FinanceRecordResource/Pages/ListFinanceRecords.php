<?php

declare(strict_types=1);

namespace App\Domains\Finances\Filament\Resources\FinanceRecordResource\Pages;

use App\Domains\Finances\Filament\Resources\FinanceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFinanceRecords extends ListRecords
{
    protected static string $resource = FinanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
