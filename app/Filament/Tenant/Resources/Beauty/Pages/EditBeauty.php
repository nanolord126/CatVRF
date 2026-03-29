<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;

final class EditBeauty extends EditRecord
{
    protected static string $resource = BeautyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить')
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function () use (&$data) {
            $data['correlation_id'] = Str::uuid()->toString();
            $data['tenant_id'] = filament()->getTenant()->id;
            
            Log::channel('audit')->info('Beauty salon updated', [
                'user_id' => auth()->id(),
                'correlation_id' => $data['correlation_id'],
                'tenant_id' => $data['tenant_id'],
                'salon_id' => $this->record->id,
                'salon_name' => $data['name'] ?? null,
            ]);
        });

        return $data;
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Beauty edit page saved', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
