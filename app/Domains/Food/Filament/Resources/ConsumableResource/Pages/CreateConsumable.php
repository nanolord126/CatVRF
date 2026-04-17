<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources\ConsumableResource\Pages;

use App\Domains\Food\Filament\Resources\ConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateConsumable extends CreateRecord
{
    protected static string $resource = ConsumableResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
