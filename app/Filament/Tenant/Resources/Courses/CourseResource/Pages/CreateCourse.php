<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Courses\CourseResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateCourse extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CourseResource::class;
}
