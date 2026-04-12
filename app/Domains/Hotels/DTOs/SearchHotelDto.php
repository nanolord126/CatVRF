<?php
declare(strict_types=1);

namespace App\Domains\Hotels\DTOs;

use Illuminate\Http\Request;
use Carbon\Carbon;

final readonly class SearchHotelDto
{
    public function __construct(
        public float $lat,
        public float $lon,
        public float $radiusKm,
        public Carbon $checkIn,
        public Carbon $checkOut,
        public int $guestsCount,
        public string $correlationId,
        public ?string $query = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            lat: (float) $request->input("lat", 0.0),
            lon: (float) $request->input("lon", 0.0),
            radiusKm: (float) $request->input("radius_km", 20.0),
            checkIn: Carbon::parse($request->input("check_in", now()->addDay()->toDateString())),
            checkOut: Carbon::parse($request->input("check_out", now()->addDays(2)->toDateString())),
            guestsCount: (int) $request->input("guests_count", 2),
            correlationId: (string) $request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid()),
            query: $request->input("query")
        );
    }

    /**
     * Преобразовать DTO в массив для передачи в Model::create() или логирование.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            $data[$property->getName()] = $value;
        }

        return $data;
    }

    /**
     * Базовая валидация данных DTO.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (property_exists($this, 'correlationId') && $this->correlationId === '') {
            throw new \InvalidArgumentException('correlationId must not be empty');
        }
    }
}
