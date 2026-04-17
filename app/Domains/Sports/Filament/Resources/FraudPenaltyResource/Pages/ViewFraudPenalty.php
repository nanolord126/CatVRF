<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\FraudPenaltyResource\Pages;

use App\Domains\Sports\Filament\Resources\FraudPenaltyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFraudPenalty extends ViewRecord
{
    protected static string $resource = FraudPenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
