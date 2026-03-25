declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AutoPartResource\Pages;

use App\Domains\Auto\Filament\Resources\AutoPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

/**
 * Просмотр детальной информации об автозапчасти с audit-логом.
 * Production 2026.
 */
final class ViewAutoPart extends ViewRecord
{
    protected static string $resource = AutoPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->after(function () {
                    $this->log->channel('audit')->info('Auto part deleted from view page', [
                        'correlation_id' => $this->record->correlation_id,
                        'part_id' => $this->record->id,
                        'sku' => $this->record->sku,
                        'user_id' => auth()->id(),
                    ]);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->log->channel('audit')->info('Auto part viewed', [
            'correlation_id' => $this->record->correlation_id,
            'part_id' => $this->record->id,
            'sku' => $this->record->sku,
            'user_id' => auth()->id(),
        ]);

        return $data;
    }
}
