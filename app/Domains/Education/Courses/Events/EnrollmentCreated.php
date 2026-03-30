<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnrollmentCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;

        public function __construct(
            public readonly Enrollment $enrollment,
            public readonly string $correlationId = '',
        ) {}
}
