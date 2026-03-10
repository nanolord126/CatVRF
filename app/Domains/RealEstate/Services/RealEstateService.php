<?php

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RealEstateService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function createProperty(array $data): Property
    {
        try {
            return DB::transaction(function () use ($data) {
                $property = Property::create([...$data, 'tenant_id' => tenant()->id]);
                AuditLog::create([
                    'entity_type' => 'Property',
                    'entity_id' => $property->id,
                    'action' => 'create',
                    'correlation_id' => $this->correlationId,
                    'user_id' => auth()->id(),
                ]);
                return $property;
            });
        } catch (Throwable $e) {
            Log::error('RealEstateService.createProperty failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function publishProperty(Property $property): Property
    {
        return DB::transaction(function () use ($property) {
            $property->update(['published' => true, 'published_at' => now()]);
            return $property;
        });
    }

    public function scheduleViewing(Property $property, array $viewing): bool
    {
        return DB::transaction(function () use ($property, $viewing) {
            return true;
        });
    }
}
