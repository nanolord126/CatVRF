<?php

declare(strict_types=1);


namespace App\Domains\Education\Courses\Http\Controllers;

use App\Domains\Education\Courses\Services\CourseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

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
    public function __construct(
        private readonly CourseService $courseService,
        private readonly FraudControlService $fraudControl
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        
        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            $this->fraudControl->check($request->all(), 'index_courses');
            
            $courses = $this->courseService->getActiveCourses(['is_b2b' => $isB2B]);
            
            return response()->json([
                'success' => true,
                'data' => $courses,
                'correlation_id' => $correlationId
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ], 403);
        }
    }
}
