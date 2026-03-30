<?php declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StreamController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use AuthorizesRequests;
        /**
         * Show live stream for event
         */
        public function show(Event $stream): View
        {
            $this->authorize('view', $stream);
            return view('live-stream', [
                'stream' => $stream,
                'title' => $stream->name,
            ]);
        }
}
