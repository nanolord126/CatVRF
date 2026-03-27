<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Domains\Tickets\Models\Event;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
final /**
 * StreamController
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class StreamController extends Controller
{
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
