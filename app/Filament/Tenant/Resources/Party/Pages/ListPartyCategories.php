<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListPartyCategories extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = PartyCategoryResource::class;

        protected function getHeaderActions(): array
        {
            return [
                CreateAction::make()
                    ->label('New Event Category')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }
}
