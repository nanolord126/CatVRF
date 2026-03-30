<?php declare(strict_types=1);

namespace App\Domains\Education\Enums;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourseStatus extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    case DRAFT = 'draft';
        case PUBLISHED = 'published';
        case ARCHIVED = 'archived';
        case DELETED = 'deleted';

        public function isPublished(): bool
        {
            return $this === self::PUBLISHED;
        }
}
