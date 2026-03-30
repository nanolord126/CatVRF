<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListPsychologists extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = PsychologistResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make(),
            ];
        }

        protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
        {
            Log::channel('audit')->info('Accessing Psychologists list', [
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
            ]);

            return parent::getTableQuery()->withCount('bookings');
        }
}
