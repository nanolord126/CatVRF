<?php

declare(strict_types=1);

namespace App\Domains\Finances\Filament\Resources\FinanceRecordResource\Pages;

use App\Domains\Finances\Filament\Resources\FinanceRecordResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFinanceRecord extends CreateRecord
{
    protected static string $resource = FinanceRecordResource::class;
}
