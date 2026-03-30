<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditLuxuryProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = LuxuryProductResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
                Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
            ];
        }

        protected function mutateFormDataBeforeSave(array $data): array
        {
            $data['correlation_id'] = (string) Str::uuid();

            Log::channel('audit')->info('Editing Luxury Product via Filament', [
                'product_id' => $this->record->id,
                'user_id' => auth()->id(),
                'correlation_id' => $data['correlation_id'],
            ]);

            return $data;
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
