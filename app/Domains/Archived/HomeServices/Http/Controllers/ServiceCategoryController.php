<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceCategoryController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here


        public function index(): JsonResponse


        {


            try {


                $categories = ServiceCategory::where('is_active', true)->get();


                return response()->json(['success' => true, 'data' => $categories, 'correlation_id' => Str::uuid()]);


            } catch (\Throwable $e) {


                return response()->json(['success' => false, 'message' => 'Failed to fetch categories'], 500);


            }


        }
}
