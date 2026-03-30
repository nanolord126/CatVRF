<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning\EventResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListEvents extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = EventResource::class;

        /**
         * Header Actions — Кнопки действий над списком.
         */
        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Создать Праздник')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }
}
