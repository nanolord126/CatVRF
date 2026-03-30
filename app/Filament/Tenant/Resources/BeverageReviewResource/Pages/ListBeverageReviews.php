<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageReviewResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListBeverageReviews extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BeverageReviewResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Manually Post Feedback')
                    ->icon('heroicon-o-pencil-square'),
            ];
        }
}
