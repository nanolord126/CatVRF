<?php declare(strict_types=1);

namespace App\Domains\Archived\MusicAndInstruments\Music\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicStockChanged extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;


        /**


         * Create a new event instance.


         */


        public function __construct(


            public MusicInstrument $instrument,


            public int $oldStock,


            public int $newStock,


            public string $correlationId


        ) {}
}
