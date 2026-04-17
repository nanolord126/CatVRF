<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\EventResource\Pages;

use App\Domains\Tickets\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
