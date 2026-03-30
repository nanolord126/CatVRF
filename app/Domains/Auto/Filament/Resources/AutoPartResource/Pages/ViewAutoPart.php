<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AutoPartResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewAutoPart extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AutoPartResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\EditAction::make(),

                Actions\DeleteAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('Auto part deleted from view page', [
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
            Log::channel('audit')->info('Auto part viewed', [
                'correlation_id' => $this->record->correlation_id,
                'part_id' => $this->record->id,
                'sku' => $this->record->sku,
                'user_id' => auth()->id(),
            ]);

            return $data;
        }
}
