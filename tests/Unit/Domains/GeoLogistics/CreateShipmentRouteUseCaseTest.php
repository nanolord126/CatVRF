<?php

namespace Tests\Unit\Domains\GeoLogistics;

use App\Domains\GeoLogistics\Application\DTOs\CreateShipmentDto;
use App\Domains\GeoLogistics\Application\UseCases\CreateShipmentRouteUseCase;
use App\Domains\GeoLogistics\Domain\Contracts\GeoRoutingServiceInterface;
use App\Domains\GeoLogistics\Domain\Contracts\ShipmentRepositoryInterface;
use App\Domains\GeoLogistics\Domain\Models\Shipment;
use App\Domains\GeoLogistics\Domain\ValueObjects\RouteDetails;
use App\Models\Tenant;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class CreateShipmentRouteUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private GeoRoutingServiceInterface|Mockery\MockInterface $geoRoutingService;
    private ShipmentRepositoryInterface|Mockery\MockInterface $shipmentRepository;
    private FraudControlService|Mockery\MockInterface $fraudControlService;
    private CreateShipmentRouteUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->geoRoutingService = Mockery::mock(GeoRoutingServiceInterface::class);
        $this->shipmentRepository = Mockery::mock(ShipmentRepositoryInterface::class);
        $this->fraudControlService = Mockery::mock(FraudControlService::class);

        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info');

        $this->useCase = new CreateShipmentRouteUseCase(
            $this->geoRoutingService,
            $this->shipmentRepository,
            $this->fraudControlService
        );
    }

    /**
     * Тест успешного создания маршрута.
     */
    public function test_it_correctly_calculates_and_creates_shipment_route(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $dto = new CreateShipmentDto(
            tenantId: $tenant->id,
            deliveryOrderId: 123,
            pickupLat: 55.7558,
            pickupLng: 37.6176,
            dropoffLat: 59.9343,
            dropoffLng: 30.3351,
            correlationId: 'test-correlation-id'
        );

        $routeDetails = new RouteDetails(
            distanceMeters: 715000,
            durationSeconds: 28800,
            polyline: 'encoded_polyline_string',
            cost: 5000.00
        );

        $this->fraudControlService
            ->shouldReceive('checkGeoManipulation')
            ->once()
            ->with((string) $dto->tenantId, $dto->correlationId)
            ->andReturn(true);

        $this->geoRoutingService
            ->shouldReceive('calculateRouteMode')
            ->once()
            ->andReturn([
                'distance_meters' => 715000,
                'duration_seconds' => 28800,
                'polyline' => 'encoded_polyline_string',
            ]);

        $this->shipmentRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function ($arg) use ($dto) {
                return $arg instanceof Shipment
                    && $arg->tenant_id === $dto->tenantId
                    && $arg->delivery_order_id === $dto->deliveryOrderId
                    && $arg->estimated_distance_meters === 715000;
            }))
            ->andReturn(true);

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(Shipment::class, $result);
    }
}

