<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserCrmResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListUserCrm extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = UserCrmResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\Action::make('export_all')
                    ->label('Экспорт всех')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray'),
            ];
        }

        protected function getHeaderWidgets(): array
        {
            return [];
        }
}
