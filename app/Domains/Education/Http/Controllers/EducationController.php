<?php

namespace App\Domains\Education\Http\Controllers;

use App\Domains\Education\Models\Course;
use App\Domains\Education\Services\EducationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EducationController extends Controller
{
    public function __construct(private EducationService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Course::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Course::class);
        return response()->json($this->service->createCourse($request->all()), 201);
    }

    public function show(Course $course): JsonResponse
    {
        return response()->json($course);
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);
        $course->update($request->all());
        return response()->json($course);
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);
        $course->delete();
        return response()->json(['message' => 'Course deleted']);
    }
}
