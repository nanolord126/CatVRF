<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Channels\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListPosts extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = PostResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()->label('Написать пост'),
            ];
        }
}
