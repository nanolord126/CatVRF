<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListEvents extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = EventResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('Entertainment Event creation started', [
                            'tenant_id' => filament()->getTenant()->id,
                            'user_id' => auth()->id(),
                        ]);
                    }),
            ];
        }
}
