<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceJobResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceJobResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditServiceJob extends EditRecord
{
    protected static string $resource = ServiceJobResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
