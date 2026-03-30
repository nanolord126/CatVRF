<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicReviewResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListMusicReviews extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = MusicReviewResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Add Review Manually')
                    ->icon('heroicon-o-plus'),
            ];
        }
}
