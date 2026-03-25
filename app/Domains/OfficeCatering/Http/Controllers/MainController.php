declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Http\Controllers;

use App\Domains\OfficeCatering\Services\OfficeCateringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

final /**
 * MainController
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MainController extends Controller
{
    public function __construct(private readonly OfficeCateringService $service) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function index(Request $request): JsonResponse
    {
        $cid = (string) Str::uuid();
        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            return response()->json(['data' => [], 'b2b' => $isB2B, 'correlation_id' => $cid]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
