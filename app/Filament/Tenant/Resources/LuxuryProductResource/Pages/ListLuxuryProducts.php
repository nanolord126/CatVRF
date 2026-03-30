<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListLuxuryProducts extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = LuxuryProductResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Добавить эксклюзив')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }
}
