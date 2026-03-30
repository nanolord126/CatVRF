<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly ApartmentService $apartmentService,


            private readonly FraudControlService $fraudControl


        ) {}


        public function manageListings(Request $request): JsonResponse


        {


            $correlationId = (string) Str::uuid();


            try {


                $isB2B = $request->has('inn') && $request->has('business_card_id');


                if (!$isB2B) {


                    return response()->json(['error' => 'B2B only', 'correlation_id' => $correlationId], 403);


                }


                $this->fraudControl->check($request->all(), 'manage_b2b_listings');


                $listings = $this->apartmentService->getB2BListings($request->all(), $correlationId);


                return response()->json([


                    'success' => true,


                    'data' => $listings,


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
