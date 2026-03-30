<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageSubscriptionResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListBeverageSubscriptions extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BeverageSubscriptionResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Grant New Subscription')
                    ->icon('heroicon-o-sparkles'),
            ];
        }
}
