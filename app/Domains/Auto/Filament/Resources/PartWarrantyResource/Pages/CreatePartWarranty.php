<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\PartWarrantyResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreatePartWarranty extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = PartWarrantyResource::class;

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
                Log::channel('audit')->info('PartWarranty created', [
                    'correlation_id' => $this->record->correlation_id,
                    'warranty_id' => $this->record->id,
                    'warranty_number' => $this->record->warranty_number,
                ]);
            });

            $this->notification->make()
                ->success()
                ->title('Гарантия оформлена')
                ->body('Номер гарантии: ' . $this->record->warranty_number)
                ->send();
        }
}
