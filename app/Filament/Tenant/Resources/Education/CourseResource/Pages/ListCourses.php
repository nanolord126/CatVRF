<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\CourseResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListCourses extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CourseResource::class;

        /**
         * Заголовки таблицы
         */
        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Создать курс')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }

        /**
         * Аудит лог на список страниц
         */
        protected function beforeFill(): void
        {
            Log::channel('audit')->info('User accessed Education Course List', [
                'tenant_id' => tenant()->id,
                'correlation_id' => (string) Str::uuid(),
            ]);
        }
}
