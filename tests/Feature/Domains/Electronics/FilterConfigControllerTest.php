<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Electronics;

use App\Domains\Electronics\Http\Controllers\FilterConfigController;
use App\Domains\Electronics\Services\ElectronicsFilterConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BaseTestCase;

final class FilterConfigControllerTest extends BaseTestCase
{
    use RefreshDatabase;

    private FilterConfigController $controller;
    private ElectronicsFilterConfigService $filterConfigService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filterConfigService = app(ElectronicsFilterConfigService::class);
        $this->controller = new FilterConfigController($this->filterConfigService);
    }

    public function test_get_all_types_returns_success_response(): void
    {
        $response = $this->controller->getAllTypes();

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('types', $data);
        $this->assertCount(15, $data['types']);
    }

    public function test_get_all_types_returns_correct_structure(): void
    {
        $response = $this->controller->getAllTypes();
        $data = json_decode($response->getContent(), true);

        $firstType = $data['types'][0];
        $this->assertArrayHasKey('value', $firstType);
        $this->assertArrayHasKey('label', $firstType);
        $this->assertArrayHasKey('icon', $firstType);
        $this->assertIsString($firstType['value']);
        $this->assertIsString($firstType['label']);
        $this->assertIsString($firstType['icon']);
    }

    public function test_get_popular_types_returns_limited_results(): void
    {
        $response = $this->controller->getPopularTypes();

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('types', $data);
        $this->assertCount(6, $data['types']); // default limit
    }

    public function test_get_popular_types_with_custom_limit(): void
    {
        $request = new \Illuminate\Http\Request(['limit' => 3]);
        $response = $this->controller->getPopularTypes($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data['types']);
    }

    public function test_get_filter_config_for_valid_type(): void
    {
        $response = $this->controller->getFilterConfig(new \Illuminate\Http\Request(), 'smartphones');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('config', $data);
        $this->assertArrayHasKey('type', $data['config']);
        $this->assertArrayHasKey('label', $data['config']);
        $this->assertArrayHasKey('icon', $data['config']);
        $this->assertArrayHasKey('primary_filters', $data['config']);
        $this->assertArrayHasKey('secondary_filters', $data['config']);
        $this->assertArrayHasKey('sort_options', $data['config']);
    }

    public function test_get_filter_config_for_invalid_type_returns_404(): void
    {
        $response = $this->controller->getFilterConfig(new \Illuminate\Http\Request(), 'invalid_type');

        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function test_get_search_patterns_for_valid_type(): void
    {
        $response = $this->controller->getSearchPatterns(new \Illuminate\Http\Request(), 'smartphones');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('patterns', $data);
        $this->assertIsArray($data['patterns']);
    }

    public function test_get_search_patterns_for_invalid_type_returns_404(): void
    {
        $response = $this->controller->getSearchPatterns(new \Illuminate\Http\Request(), 'invalid_type');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_get_type_suggestions_with_valid_query(): void
    {
        $request = new \Illuminate\Http\Request([
            'query' => 'Apple',
            'limit' => 10,
        ]);

        $response = $this->controller->getTypeSuggestions($request, 'smartphones');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertArrayHasKey('query', $data);
        $this->assertEquals('Apple', $data['query']);
    }

    public function test_get_type_suggestions_without_query_returns_validation_error(): void
    {
        $request = new \Illuminate\Http\Request([
            'limit' => 10,
        ]);

        $response = $this->controller->getTypeSuggestions($request, 'smartphones');

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_get_type_suggestions_with_custom_limit(): void
    {
        $request = new \Illuminate\Http\Request([
            'query' => 'Samsung',
            'limit' => 5,
        ]);

        $response = $this->controller->getTypeSuggestions($request, 'smartphones');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertLessThanOrEqual(5, count($data['suggestions']));
    }

    public function test_get_type_hierarchy_returns_correct_structure(): void
    {
        $response = $this->controller->getTypeHierarchy();

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('hierarchy', $data);
        
        $hierarchy = $data['hierarchy'];
        $this->assertArrayHasKey('mobile', $hierarchy);
        $this->assertArrayHasKey('computers', $hierarchy);
        $this->assertArrayHasKey('audio_video', $hierarchy);
        
        $this->assertArrayHasKey('label', $hierarchy['mobile']);
        $this->assertArrayHasKey('types', $hierarchy['mobile']);
        $this->assertIsArray($hierarchy['mobile']['types']);
    }

    public function test_validate_filters_with_valid_filters(): void
    {
        $request = new \Illuminate\Http\Request([
            'brands' => ['Apple', 'Samsung'],
            'screen_size' => ['6.5"'],
        ]);

        $response = $this->controller->validateFilters($request, 'smartphones');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('valid', $data);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('valid_filters', $data);
    }

    public function test_validate_filters_with_invalid_filters(): void
    {
        $request = new \Illuminate\Http\Request([
            'brands' => ['InvalidBrand'],
        ]);

        $response = $this->controller->validateFilters($request, 'smartphones');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['valid']);
        $this->assertNotEmpty($data['errors']);
    }

    public function test_validate_filters_with_invalid_type(): void
    {
        $request = new \Illuminate\Http\Request([
            'brands' => ['Apple'],
        ]);

        $response = $this->controller->validateFilters($request, 'invalid_type');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['valid']);
    }

    public function test_smartphones_filter_config_has_specific_filters(): void
    {
        $response = $this->controller->getFilterConfig(new \Illuminate\Http\Request(), 'smartphones');
        $data = json_decode($response->getContent(), true);
        
        $config = $data['config'];
        $this->assertEquals('smartphones', $config['type']);
        $this->assertEquals('Смартфоны', $config['label']);
        
        $ramFilter = collect($config['secondary_filters'])->firstWhere('key', 'ram');
        $this->assertNotNull($ramFilter);
        $this->assertContains('8GB', $ramFilter['options']);
    }

    public function test_laptops_filter_config_has_cpu_filter(): void
    {
        $response = $this->controller->getFilterConfig(new \Illuminate\Http\Request(), 'laptops');
        $data = json_decode($response->getContent(), true);
        
        $config = $data['config'];
        $cpuFilter = collect($config['secondary_filters'])->firstWhere('key', 'cpu');
        $this->assertNotNull($cpuFilter);
        $this->assertContains('Intel Core i7', $cpuFilter['options']);
    }

    public function test_all_types_have_sort_options(): void
    {
        $types = ['smartphones', 'laptops', 'tablets', 'headphones', 'tv', 'cameras', 'smartwatches'];

        foreach ($types as $type) {
            $response = $this->controller->getFilterConfig(new \Illuminate\Http\Request(), $type);
            $data = json_decode($response->getContent(), true);
            
            $this->assertArrayHasKey('sort_options', $data['config']);
            $this->assertNotEmpty($data['config']['sort_options']);
        }
    }

    public function test_controller_injects_service_correctly(): void
    {
        $controller = new FilterConfigController($this->filterConfigService);
        
        $this->assertInstanceOf(FilterConfigController::class, $controller);
    }

    public function test_get_all_types_response_is_json(): void
    {
        $response = $this->controller->getAllTypes();
        
        $this->assertIsString($response->getContent());
        json_decode($response->getContent());
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    public function test_get_filter_config_response_is_json(): void
    {
        $response = $this->controller->getFilterConfig(new \Illuminate\Http\Request(), 'smartphones');
        
        $this->assertIsString($response->getContent());
        json_decode($response->getContent());
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    public function test_get_type_hierarchy_response_is_json(): void
    {
        $response = $this->controller->getTypeHierarchy();
        
        $this->assertIsString($response->getContent());
        json_decode($response->getContent());
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }
}
