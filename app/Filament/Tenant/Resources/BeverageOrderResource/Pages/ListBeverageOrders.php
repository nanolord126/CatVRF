<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageOrderResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListBeverageOrders extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BeverageOrderResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Manually Enter Order')
                    ->icon('heroicon-o-keyboard'),
            ];
        }
}
