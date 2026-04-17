<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AIDiagnosticsHistoryResource\Pages;

use App\Domains\Auto\Filament\Resources\AIDiagnosticsHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewDiagnosticsHistory extends ViewRecord
{
    protected static string $resource = AIDiagnosticsHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
