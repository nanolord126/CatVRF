<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AttendanceRecorded extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public function __construct(
            public Attendance $attendance,
            public string $correlationId,
        ) {}
}
