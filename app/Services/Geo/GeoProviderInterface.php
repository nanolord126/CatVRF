<?php declare(strict_types=1);

namespace App\Services\Geo;

/**
 * Interface for geolocation providers
 * All map providers must implement this interface
 */
interface GeoProviderInterface
{
    /**
     * Calculate distance between two points in kilometers
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float;

    /**
     * Calculate route between two points
     * @return array{distance_km: float, duration_min: int, polyline: string}
     */
    public function calculateRoute(float $lat1, float $lon1, float $lat2, float $lon2): array;

    /**
     * Geocode address to coordinates
     * @return array{lat: float, lon: float}|null
     */
    public function geocode(string $address): ?array;

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode(float $lat, float $lon): ?string;

    /**
     * Get provider name
     */
    public function getProviderName(): string;

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool;
}
