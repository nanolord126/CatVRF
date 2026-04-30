<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalTaskResource\Pages;

use App\Filament\Resources\InternalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateInternalTask extends CreateRecord
{
    protected static string $resource = InternalTaskResource::class;
}
