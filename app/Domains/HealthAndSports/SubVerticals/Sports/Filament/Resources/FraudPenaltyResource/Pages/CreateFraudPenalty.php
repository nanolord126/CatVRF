<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\FraudPenaltyResource\Pages;

use App\Domains\Sports\Filament\Resources\FraudPenaltyResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFraudPenalty extends CreateRecord
{
    protected static string $resource = FraudPenaltyResource::class;
}
