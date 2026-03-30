<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly B2BService $service) {}


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
