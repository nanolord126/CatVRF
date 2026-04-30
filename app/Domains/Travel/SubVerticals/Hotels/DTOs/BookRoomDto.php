<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\DTOs;

use Guard;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;

final readonly class BookRoomDto
{
    public function __construct(private readonly Guard $guard,
        public int $hotelId,
        public int $roomId,
        public int $customerId,
        public Carbon $checkIn,
        public Carbon $checkOut,
        public int $guestsCount,
        public ?string $specialRequests,
        public string $correlationId) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            hotelId: (int) $request->input('hotel_id', 0),
            roomId: (int) $request->input('room_id', 0),
            customerId: (int) ($this->guard /* TODO: inject via constructor DI */ /* TODO: inject via DI */->id() ?? 0),
            checkIn: Carbon::parse($request->input('check_in')),
            checkOut: Carbon::parse($request->input('check_out')),
            guestsCount: (int) $request->input('guests_count', 1),
            specialRequests: $request->input('special_requests'),
            correlationId: (string) $request->header('X-Correlation-ID', (string) Str::uuid())
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
