<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicLessonResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListMusicLessons extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = MusicLessonResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('New Lesson')
                    ->icon('heroicon-o-plus'),
            ];
        }
}
