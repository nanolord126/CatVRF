<?php declare(strict_types=1);

namespace Tests\Feature;

use Livewire\Livewire;
use Tests\TestCase;

final class ThreeDVisualizationTest extends TestCase
{
    private Product3DService $productService;
    private Room3DVisualizerService $roomService;
    private VehicleVisualizerService $vehicleService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productService = app(Product3DService::class);
        $this->roomService = app(Room3DVisualizerService::class);
        $this->vehicleService = app(VehicleVisualizerService::class);
    }

    public function test_product_3d_card_renders(): void
    {
        Livewire::test(ProductCard3D::class, ['productId' => 1, 'vertical' => 'Electronics'])
            ->assertStatus(200)
            ->assertRenderSuccessfully();
    }

    public function test_product_3d_card_rotation(): void
    {
        Livewire::test(ProductCard3D::class, ['productId' => 1, 'vertical' => 'Electronics'])
            ->call('rotate', 'left')
            ->assertSet('rotationY', -15)
            ->call('rotate', 'right')
            ->assertSet('rotationY', 0);
    }

    public function test_product_3d_card_zoom(): void
    {
        Livewire::test(ProductCard3D::class, ['productId' => 1, 'vertical' => 'Electronics'])
            ->call('zoomIn')
            ->assertSet('zoom', 1.1)
            ->call('zoomOut')
            ->assertSet('zoom', 1.0);
    }

    public function test_room_3d_tour_renders(): void
    {
        Livewire::test(Room3DTour::class, ['roomId' => 1, 'hotelId' => '1'])
            ->assertStatus(200)
            ->assertRenderSuccessfully();
    }

    public function test_room_3d_tour_view_change(): void
    {
        Livewire::test(Room3DTour::class, ['roomId' => 1, 'hotelId' => '1'])
            ->call('viewFrom', 'bed')
            ->assertSet('currentView.position', [-2, 1.5, 0]);
    }

    public function test_property_3d_viewer_renders(): void
    {
        Livewire::test(Property3DViewer::class, ['propertyId' => 1])
            ->assertStatus(200)
            ->assertRenderSuccessfully();
    }

    public function test_property_3d_viewer_navigation(): void
    {
        Livewire::test(Property3DViewer::class, ['propertyId' => 1])
            ->call('nextFloor')
            ->call('selectRoom', 0);
    }

    public function test_room_visualization_generation(): void
    {
        $roomData = [
            'type' => 'suite',
            'length' => 6,
            'width' => 5,
            'height' => 2.8,
            'furniture' => ['bed', 'sofa', 'desk'],
        ];

        $visualization = $this->roomService->generateRoomVisualization($roomData);

        $this->assertArrayHasKey('room_id', $visualization);
        $this->assertArrayHasKey('type', $visualization);
        $this->assertArrayHasKey('dimensions', $visualization);
        $this->assertArrayHasKey('models_3d', $visualization);
    }

    public function test_property_visualization_generation(): void
    {
        $propertyData = [
            'id' => 1,
            'type' => 'apartment',
            'rooms' => [
                ['id' => 1, 'name' => 'Living Room', 'floor' => 0],
                ['id' => 2, 'name' => 'Bedroom', 'floor' => 1],
            ],
        ];

        $visualization = $this->roomService->generatePropertyVisualization($propertyData);

        $this->assertArrayHasKey('property_id', $visualization);
        $this->assertArrayHasKey('type', $visualization);
        $this->assertArrayHasKey('rooms', $visualization);
        $this->assertCount(2, $visualization['rooms']);
    }

    public function test_vehicle_visualization_generation(): void
    {
        $vehicleData = [
            'id' => 1,
            'type' => 'car',
            'brand' => 'Tesla',
            'model' => 'Model 3',
            'color' => '#000000',
            'wheels' => 4,
        ];

        $visualization = $this->vehicleService->generateVehicleVisualization($vehicleData);

        $this->assertArrayHasKey('vehicle_id', $visualization);
        $this->assertArrayHasKey('type', $visualization);
        $this->assertArrayHasKey('exterior', $visualization);
        $this->assertArrayHasKey('interior', $visualization);
        $this->assertArrayHasKey('camera_angles', $visualization);
    }

    public function test_3d_model_validation(): void
    {
        $this->assertTrue($this->productService->validate3DModel('model.glb'));
        $this->assertTrue($this->productService->validate3DModel('model.gltf'));
        $this->assertFalse($this->productService->validate3DModel('model.txt'));
    }

    public function test_ar_view_toggle(): void
    {
        Livewire::test(ProductCard3D::class, ['productId' => 1, 'vertical' => 'Jewelry'])
            ->call('enableARView')
            ->assertDispatched('enable-ar');
    }
}
