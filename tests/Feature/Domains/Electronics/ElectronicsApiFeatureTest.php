<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Electronics;

use App\Domains\Electronics\Models\ElectronicProduct;
use App\Domains\Electronics\Models\ElectronicOrder;
use App\Domains\Electronics\Models\ElectronicsStore;
use App\Domains\Electronics\Models\ElectronicsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BaseTestCase;

final class ElectronicsApiFeatureTest extends BaseTestCase
{
    use RefreshDatabase;

    public function test_index_returns_electronics_products(): void
    {
        ElectronicProduct::factory()->count(5)->create([
            'availability_status' => 'in_stock',
        ]);

        $response = $this->getJson('/api/v1/electronics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'correlation_id',
                'data',
            ])
            ->assertJsonPath('success', true);
    }

    public function test_index_filters_by_b2b_availability(): void
    {
        ElectronicProduct::factory()->create([
            'availability_status' => 'in_stock',
            'is_b2b_available' => true,
        ]);

        ElectronicProduct::factory()->create([
            'availability_status' => 'in_stock',
            'is_b2b_available' => false,
        ]);

        $response = $this->getJson('/api/v1/electronics?is_b2b=1');

        $response->assertStatus(200);
        $data = $response->json('data.data');

        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_b2b_available']);
    }

    public function test_show_returns_single_product(): void
    {
        $product = ElectronicProduct::factory()->create([
            'availability_status' => 'in_stock',
        ]);

        $response = $this->getJson("/api/v1/electronics/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/electronics/99999');

        $response->assertStatus(404);
    }

    public function test_search_endpoint_returns_results(): void
    {
        ElectronicProduct::factory()->create([
            'name' => 'iPhone 15 Pro',
            'brand' => 'Apple',
            'availability_status' => 'in_stock',
        ]);

        ElectronicProduct::factory()->create([
            'name' => 'Samsung Galaxy S24',
            'brand' => 'Samsung',
            'availability_status' => 'in_stock',
        ]);

        $response = $this->getJson('/api/v1/electronics/search?q=iPhone');

        $response->assertStatus(200);
    }

    public function test_compare_endpoint_compares_products(): void
    {
        $product1 = ElectronicProduct::factory()->create([
            'availability_status' => 'in_stock',
        ]);

        $product2 = ElectronicProduct::factory()->create([
            'availability_status' => 'in_stock',
        ]);

        $response = $this->getJson("/api/v1/electronics/compare?ids={$product1->id},{$product2->id}");

        $response->assertStatus(200);
    }

    public function test_analytics_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/electronics/analytics');

        $response->assertStatus(401);
    }

    public function test_analytics_endpoint_returns_data_for_authenticated_user(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/electronics/analytics');

        $response->assertStatus(200);
    }

    public function test_order_creation_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/electronics/orders', [
            'product_id' => 1,
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_order_creation_validates_required_fields(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/electronics/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'quantity']);
    }

    public function test_order_creation_succeeds_with_valid_data(): void
    {
        $user = $this->createUser();
        $product = ElectronicProduct::factory()->create([
            'availability_status' => 'in_stock',
            'current_stock' => 10,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/electronics/orders', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'order_id',
                'correlation_id',
            ]);
    }

    public function test_my_orders_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/electronics/my-orders');

        $response->assertStatus(401);
    }

    public function test_my_orders_returns_user_orders(): void
    {
        $user = $this->createUser();
        ElectronicOrder::factory()->count(3)->create([
            'client_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/electronics/my-orders');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_filter_config_returns_types(): void
    {
        $response = $this->getJson('/api/v1/electronics/types');

        $response->assertStatus(200);
    }

    public function test_filter_config_popular_returns_popular_types(): void
    {
        $response = $this->getJson('/api/v1/electronics/types/popular');

        $response->assertStatus(200);
    }

    public function test_search_filters_endpoint_returns_filters(): void
    {
        $response = $this->getJson('/api/v1/electronics/search/filters');

        $response->assertStatus(200);
    }

    public function test_search_suggestions_endpoint_returns_suggestions(): void
    {
        $response = $this->getJson('/api/v1/electronics/search/suggestions?q=laptop');

        $response->assertStatus(200);
    }

    public function test_search_popular_returns_popular_searches(): void
    {
        $response = $this->getJson('/api/v1/electronics/search/popular');

        $response->assertStatus(200);
    }

    public function test_analytics_sales_endpoint_returns_sales_data(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/electronics/analytics/sales');

        $response->assertStatus(200);
    }

    public function test_analytics_traffic_endpoint_returns_traffic_data(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/electronics/analytics/traffic');

        $response->assertStatus(200);
    }

    public function test_analytics_top_products_endpoint_returns_top_products(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/electronics/analytics/top-products');

        $response->assertStatus(200);
    }

    protected function createUser()
    {
        return \App\Models\User::factory()->create();
    }
}
