<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\FraudPenaltyResource\Pages;

use App\Domains\Sports\Filament\Resources\FraudPenaltyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFraudPenalties extends ListRecords
{
    protected static string $resource = FraudPenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
