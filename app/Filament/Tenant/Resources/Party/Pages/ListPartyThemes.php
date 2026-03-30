<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListPartyThemes extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = PartyThemeResource::class;

        protected function getHeaderActions(): array
        {
            return [
                CreateAction::make()
                    ->label('New Theme Collection')
                    ->icon('heroicon-o-sparkles'),
            ];
        }
}
