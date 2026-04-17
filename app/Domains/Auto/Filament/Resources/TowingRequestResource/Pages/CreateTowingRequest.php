<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TowingRequestResource\Pages;

use App\Domains\Auto\Filament\Resources\TowingRequestResource;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

final class CreateTowingRequest extends CreateRecord
{
    protected static string $resource = TowingRequestResource::class;

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
        Notification::make()
            ->success()
            ->title('Заявка на эвакуатор создана')
            ->body('Ожидайте назначения водителя')
            ->send();
    }
}
