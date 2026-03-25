declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Controllers;

use App\Domains\Pharmacy\Services\B2BService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

final /**
 * B2BController
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BController extends Controller
{
    public function __construct(private readonly B2BService $service) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function store(Request $request): JsonResponse
    {
        $cid = (string) Str::uuid();
        try {
            $order = $this->service->placeOrder($request->all(), $cid);
            return response()->json(['order' => $order, 'correlation_id' => $cid]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
