<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\DTO;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RideRequestDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            public int $passengerId,


            public string $pickupAddress,


            public float $pickupLat,


            public float $pickupLon,


            public string $dropoffAddress,


            public float $dropoffLat,


            public float $dropoffLon,


            public float $estimatedDistance,


            public int $estimatedMinutes,


            public string $correlationId,


            public ?int $fleetId = null,


            public string $source = 'api_v1',


            public array $metadata = []


        ) {}


        /**


         * Статический конструктор из массива (FormRequest).


         */


        public static function fromArray(array $data, int $userId): self


        {


            return new self(


                passengerId: $userId,


                pickupAddress: (string)($data['pickup_address'] ?? ''),


                pickupLat: (float)($data['pickup_lat'] ?? 0.0),


                pickupLon: (float)($data['pickup_lon'] ?? 0.0),


                dropoffAddress: (string)($data['dropoff_address'] ?? ''),


                dropoffLat: (float)($data['dropoff_lat'] ?? 0.0),


                dropoffLon: (float)($data['dropoff_lon'] ?? 0.0),


                estimatedDistance: (float)($data['estimated_distance'] ?? 0.0),


                estimatedMinutes: (int)($data['estimated_minutes'] ?? 0),


                correlationId: (string)($data['correlation_id'] ?? Str::uuid()),


                fleetId: isset($data['fleet_id']) ? (int)$data['fleet_id'] : null,


                source: (string)($data['source'] ?? 'api_v1'),


                metadata: (array)($data['metadata'] ?? [])


            );


        }


        /**


         * Конвертация в массив для TaxiService.


         */


        public function toServiceArray(): array


        {


            return [


                'pickup_address' => $this->pickupAddress,


                'pickup_lat' => $this->pickupLat,


                'pickup_lon' => $this->pickupLon,


                'dropoff_address' => $this->dropoffAddress,


                'dropoff_lat' => $this->dropoffLat,


                'dropoff_lon' => $this->dropoffLon,


                'estimated_distance' => $this->estimatedDistance,


                'estimated_minutes' => $this->estimatedMinutes,


                'source' => $this->source,


                'metadata' => $this->metadata


            ];


        }
}
