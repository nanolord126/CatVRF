<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListLanguageSchools extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = LanguageSchoolResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make(),
            ];
        }
    }

    final class CreateLanguageSchool extends \Filament\Resources\Pages\CreateRecord
    {
        protected static string $resource = LanguageSchoolResource::class;
    }

    final class EditLanguageSchool extends \Filament\Resources\Pages\EditRecord
    {
        protected static string $resource = LanguageSchoolResource::class;
    }

    // Повторяем для учителей
    namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

    use App\Filament\Tenant\Resources\LanguageLearning\LanguageTeacherResource;
    use Filament\Resources\Pages\ListRecords;

    final class ListLanguageTeachers extends ListRecords
    {
        protected static string $resource = LanguageTeacherResource::class;
    }

    final class CreateLanguageTeacher extends \Filament\Resources\Pages\CreateRecord
    {
        protected static string $resource = LanguageTeacherResource::class;
    }

    final class EditLanguageTeacher extends \Filament\Resources\Pages\EditRecord
    {
        protected static string $resource = LanguageTeacherResource::class;
}
