<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditDentalClinic extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = DentalClinicResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ];
        }

        protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
        {
            return DB::transaction(function () use ($record, $data) {
                $oldName = $record->name;
                $record = parent::handleRecordUpdate($record, $data);

                Log::channel('audit')->info('Dental Clinic Updated', [
                    'clinic_id' => $record->id,
                    'old_name' => $oldName,
                    'new_name' => $record->name,
                    'correlation_id' => $record->correlation_id
                ]);

                return $record;
            });
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
