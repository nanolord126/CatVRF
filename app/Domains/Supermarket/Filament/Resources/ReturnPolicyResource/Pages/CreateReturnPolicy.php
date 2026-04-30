<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\ReturnPolicyResource\Pages;

use App\Domains\Supermarket\Filament\Resources\ReturnPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnPolicy extends CreateRecord
{
    protected static string $resource = ReturnPolicyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
