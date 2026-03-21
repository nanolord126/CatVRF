<?php declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\Listing;
use Modules\RealEstate\Models\Viewing;
use Illuminate\Support\Str;

/**
 * Real Estate Property Management Service
 * CANON 2026 - Production Ready
 */
final class PropertyService
{
    public function createProperty(array $data, int $tenantId, string $correlationId): Property
    {
        return DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating real estate property', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ]);

            return Property::create([
                'tenant_id' => $tenantId,
                'address' => $data['address'],
                'geo_point' => $data['geo_point'] ?? null,
                'type' => $data['type'], // apartment, house, land, commercial
                'area' => $data['area'],
                'rooms' => $data['rooms'] ?? null,
                'floor' => $data['floor'] ?? null,
                'description' => $data['description'],
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function createListing(array $data, int $propertyId, string $type, string $correlationId): Listing
    {
        return DB::transaction(function () use ($data, $propertyId, $type, $correlationId) {
            Log::channel('audit')->info('Creating real estate listing', [
                'correlation_id' => $correlationId,
                'property_id' => $propertyId,
                'type' => $type,
            ]);

            return Listing::create([
                'property_id' => $propertyId,
                'type' => $type, // sale or rental
                'price' => $data['price'],
                'commission_percent' => $data['commission_percent'] ?? 14,
                'description' => $data['description'] ?? null,
                'status' => 'active',
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function createViewing(array $data, int $propertyId, int $userId, string $correlationId): Viewing
    {
        return DB::transaction(function () use ($data, $propertyId, $userId, $correlationId) {
            Log::channel('audit')->info('Creating property viewing', [
                'correlation_id' => $correlationId,
                'property_id' => $propertyId,
                'user_id' => $userId,
            ]);

            return Viewing::create([
                'property_id' => $propertyId,
                'user_id' => $userId,
                'viewing_date' => $data['viewing_date'],
                'status' => 'scheduled',
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function getPropertyStats(Property $property): array
    {
        $listing = $property->listing;
        $viewings = Viewing::query()->where('property_id', $property->id)->count();

        return [
            'type' => $property->type,
            'price' => $listing->price ?? 0,
            'viewings' => $viewings,
            'rating' => $property->rating ?? 0,
        ];
    }
}
