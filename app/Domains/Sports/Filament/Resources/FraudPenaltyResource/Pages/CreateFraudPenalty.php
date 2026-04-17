<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\FraudPenaltyResource\Pages;

use App\Domains\Sports\Filament\Resources\FraudPenaltyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFraudPenalty extends CreateRecord
{
    protected static string $resource = FraudPenaltyResource::class;
}
