<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2C\DTOs;

use App\Domains\RealEstate\Domain\Entities\Property;

final readonly class PropertyDTO
{
    public function __construct(
        public string  $id,
        public string  $title,
        public string  $description,
        public string  $address,
        public float   $lat,
        public float   $lon,
        public string  $type,
        public string  $typeLabel,
        public string  $status,
        public string  $statusLabel,
        public int     $priceKopecks,
        public float   $priceRubles,
        public float   $areaSqm,
        public int     $pricePerSqmKopecks,
        public int     $rooms,
        public int     $floor,
        public int     $totalFloors,
        public string  $agentId,
        public array   $photos,
        public array   $documents) {}

    public static function fromEntity(Property $property): self
    {
        $priceKopecks = $property->getPrice()->getAmountKopecks();
        $areaSqm      = $property->getArea()->getSquareMeters();

        return new self(
            id: $property->getId()->getValue(),
            title: $property->getTitle(),
            description: $property->getDescription(),
            address: $property->getAddress(),
            lat: $property->getCoordinates()->getLatitude(),
            lon: $property->getCoordinates()->getLongitude(),
            type: $property->getType()->value,
            typeLabel: $property->getType()->label(),
            status: $property->getStatus()->value,
            statusLabel: $property->getStatus()->label(),
            priceKopecks: $priceKopecks,
            priceRubles: $property->getPrice()->getAmountRubles(),
            areaSqm: $areaSqm,
            pricePerSqmKopecks: $areaSqm > 0 ? (int) round($priceKopecks / $areaSqm) : 0,
            rooms: $property->getRooms(),
            floor: $property->getFloor(),
            totalFloors: $property->getTotalFloors(),
            agentId: $property->getAgentId()->getValue(),
            photos: $property->getPhotos(),
            documents: $property->getDocuments(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'address'              => $this->address,
            'coordinates'          => ['lat' => $this->lat, 'lon' => $this->lon],
            'type'                 => $this->type,
            'type_label'           => $this->typeLabel,
            'status'               => $this->status,
            'status_label'         => $this->statusLabel,
            'price_kopecks'        => $this->priceKopecks,
            'price_rubles'         => $this->priceRubles,
            'area_sqm'             => $this->areaSqm,
            'price_per_sqm_kopecks' => $this->pricePerSqmKopecks,
            'rooms'                => $this->rooms,
            'floor'                => $this->floor,
            'total_floors'         => $this->totalFloors,
            'agent_id'             => $this->agentId,
            'photos'               => $this->photos,
            'documents'            => $this->documents,
        ];
    }
}
