<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PsychologyEventServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $listen = [
            PsychologicalBookingCreated::class => [
                HandlePsychologicalBookingCreated::class,
            ],
        ];

        public function boot(): void
        {
            parent::boot();
        }
}
