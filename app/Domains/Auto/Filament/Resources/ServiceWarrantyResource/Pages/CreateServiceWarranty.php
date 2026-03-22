<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages;

use App\Domains\Auto\Filament\Resources\ServiceWarrantyResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateServiceWarranty extends CreateRecord
{
    protected static string $resource = ServiceWarrantyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;

        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            Log::channel('audit')->info('ServiceWarranty created', [
                'correlation_id' => $this->record->correlation_id,
                'warranty_id' => $this->record->id,
                'warranty_number' => $this->record->warranty_number,
            ]);
        });

        Notification::make()
            ->success()
            ->title('Гарантия на ремонт оформлена')
            ->body('Номер гарантии: ' . $this->record->warranty_number)
            ->send();
    }
}
