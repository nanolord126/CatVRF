<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListDentalClinics extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = DentalClinicResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->label('Register New Clinic'),
            ];
        }

        public function mount(): void
        {
            parent::mount();

            Log::channel('audit')->info('Dental Clinic Directory accessed', [
                'tenant_id' => tenant()->id ?? 'system',
                'user_id' => auth()->id(),
                'correlation_id' => request()->header('X-Correlation-ID')
            ]);
        }
}
