<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListLanguageCourses extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = LanguageCourseResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make(),
            ];
        }
    }

    final class CreateLanguageCourse extends \Filament\Resources\Pages\CreateRecord
    {
        protected static string $resource = LanguageCourseResource::class;
    }

    final class EditLanguageCourse extends \Filament\Resources\Pages\EditRecord
    {
        protected static string $resource = LanguageCourseResource::class;
    }

    // Enrollment Pages
    namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

    use App\Filament\Tenant\Resources\LanguageLearning\LanguageEnrollmentResource;
    use Filament\Resources\Pages\ListRecords;

    final class ListLanguageEnrollments extends ListRecords
    {
        protected static string $resource = LanguageEnrollmentResource::class;
}
