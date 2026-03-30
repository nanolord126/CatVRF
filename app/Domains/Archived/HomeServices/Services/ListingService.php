<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly FraudControlService $fraudControlService,) {}


        public function createListing(


            int $contractorId,


            int $categoryId,


            string $name,


            string $description,


            string $type,


            float $basePrice,


            string $correlationId


        ): ServiceListing {


            try {


                            $this->fraudControlService->check(


                    auth()->id() ?? 0,


                    __CLASS__ . '::' . __FUNCTION__,


                    0,


                    request()->ip(),


                    null,


                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()


                );


                DB::transaction(function () use ($contractorId, $categoryId, $name, $description, $type, $basePrice, $correlationId) {


                    $listing = ServiceListing::create([


                        'tenant_id' => tenant('id'),


                        'contractor_id' => $contractorId,


                        'category_id' => $categoryId,


                        'name' => $name,


                        'description' => $description,


                        'type' => $type,


                        'base_price' => $basePrice,


                        'is_active' => true,


                        'correlation_id' => $correlationId,


                    ]);


                    \Log::channel('audit')->info('Service listing created', [


                        'listing_id' => $listing->id,


                        'contractor_id' => $contractorId,


                        'correlation_id' => $correlationId,


                    ]);


                    return $listing;


                });


            } catch (\Throwable $e) {


                \Log::channel('audit')->error('Failed to create listing', ['error' => $e->getMessage()]);


                throw $e;


            }


        }


        public function updateListing(ServiceListing $listing, array $data, string $correlationId): ServiceListing


        {


            try {


                            $this->fraudControlService->check(


                    auth()->id() ?? 0,


                    __CLASS__ . '::' . __FUNCTION__,


                    0,


                    request()->ip(),


                    null,


                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()


                );


                DB::transaction(function () use ($listing, $data, $correlationId) {


                    $listing->update($data + ['correlation_id' => $correlationId]);


                    return $listing;


                });


            } catch (\Throwable $e) {


                \Log::channel('audit')->error('Failed to update listing', ['error' => $e->getMessage()]);


                throw $e;


            }


        }
}
