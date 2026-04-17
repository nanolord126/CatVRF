<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\DTOs\FilterConfigDto;
use App\Domains\Electronics\Enums\ElectronicsType;
use App\Domains\Electronics\Services\ElectronicsFilterConfigService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\BaseTestCase;

final class ElectronicsFilterConfigServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private ElectronicsFilterConfigService $service;
    private Cache|MockObject $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(Cache::class);
        $this->service = new ElectronicsFilterConfigService(
            $this->cache,
        );
    }

    public function test_get_all_types_returns_all_electronics_types(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $types = $this->service->getAllTypes();

        $this->assertCount(15, $types);
        $this->assertEquals('smartphones', $types[0]['value']);
        $this->assertEquals('Смартфоны', $types[0]['label']);
        $this->assertEquals('smartphone', $types[0]['icon']);
    }

    public function test_get_filter_config_for_valid_type(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $config = $this->service->getFilterConfig('smartphones');

        $this->assertInstanceOf(FilterConfigDto::class, $config);
        $this->assertEquals('smartphones', $config->type);
        $this->assertEquals('Смартфоны', $config->label);
        $this->assertCount(2, $config->primaryFilters);
        $this->assertGreaterThan(0, count($config->secondaryFilters));
        $this->assertGreaterThan(0, count($config->sortOptions));
    }

    public function test_get_filter_config_for_invalid_type_returns_null(): void
    {
        $this->cache->expects($this->never())
            ->method('remember');

        $config = $this->service->getFilterConfig('invalid_type');

        $this->assertNull($config);
    }

    public function test_get_filter_configs_for_multiple_types(): void
    {
        $this->cache->expects($this->exactly(3))
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $configs = $this->service->getFilterConfigs(['smartphones', 'laptops', 'invalid_type']);

        $this->assertCount(2, $configs);
        $this->assertEquals('smartphones', $configs[0]->type);
        $this->assertEquals('laptops', $configs[1]->type);
    }

    public function test_get_popular_types_returns_limited_number(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $types = $this->service->getPopularTypes(5);

        $this->assertCount(5, $types);
    }

    public function test_get_search_patterns_for_type(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $patterns = $this->service->getSearchPatterns('smartphones');

        $this->assertArrayHasKey('type', $patterns);
        $this->assertArrayHasKey('label', $patterns);
        $this->assertArrayHasKey('patterns', $patterns);
        $this->assertEquals('smartphones', $patterns['type']);
        $this->assertIsArray($patterns['patterns']);
    }

    public function test_get_search_patterns_for_invalid_type_returns_empty(): void
    {
        $patterns = $this->service->getSearchPatterns('invalid_type');

        $this->assertEmpty($patterns);
    }

    public function test_get_type_search_suggestions(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $suggestions = $this->service->getTypeSearchSuggestions('smartphones', 'Apple', 10);

        $this->assertIsArray($suggestions);
        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('label', $suggestion);
            $this->assertArrayHasKey('value', $suggestion);
            $this->assertArrayHasKey('category', $suggestion);
        }
    }

    public function test_get_type_search_suggestions_with_short_query(): void
    {
        $suggestions = $this->service->getTypeSearchSuggestions('smartphones', 'A', 10);

        $this->assertIsArray($suggestions);
    }

    public function test_get_type_hierarchy(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $hierarchy = $this->service->getTypeHierarchy();

        $this->assertArrayHasKey('mobile', $hierarchy);
        $this->assertArrayHasKey('computers', $hierarchy);
        $this->assertArrayHasKey('audio_video', $hierarchy);
        $this->assertArrayHasKey('gaming', $hierarchy);
        $this->assertArrayHasKey('smart_home', $hierarchy);
        $this->assertArrayHasKey('auto', $hierarchy);
        $this->assertArrayHasKey('appliances', $hierarchy);

        $this->assertArrayHasKey('label', $hierarchy['mobile']);
        $this->assertArrayHasKey('types', $hierarchy['mobile']);
        $this->assertContains('smartphones', $hierarchy['mobile']['types']);
    }

    public function test_validate_filter_values_with_valid_filters(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $filters = [
            'brands' => ['Apple', 'Samsung'],
            'screen_size' => ['6.5"', '6.7"'],
        ];

        $validation = $this->service->validateFilterValues('smartphones', $filters);

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        $this->assertArrayHasKey('valid_filters', $validation);
    }

    public function test_validate_filter_values_with_invalid_type(): void
    {
        $validation = $this->service->validateFilterValues('invalid_type', []);

        $this->assertFalse($validation['valid']);
        $this->assertArrayHasKey('errors', $validation);
    }

    public function test_validate_filter_values_with_invalid_option(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $filters = [
            'brands' => ['InvalidBrand'],
        ];

        $validation = $this->service->validateFilterValues('smartphones', $filters);

        $this->assertFalse($validation['valid']);
        $this->assertArrayHasKey('brands', $validation['errors']);
    }

    public function test_validate_filter_values_with_range_out_of_bounds(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $filters = [
            'screen_size' => [
                'min' => 3.0,
                'max' => 10.0,
            ],
        ];

        $validation = $this->service->validateFilterValues('smartphones', $filters);

        $this->assertFalse($validation['valid']);
        $this->assertArrayHasKey('screen_size', $validation['errors']);
    }

    public function test_clear_cache_for_specific_type(): void
    {
        $this->cache->expects($this->once())
            ->method('forget')
            ->with('electronics_filter_config_smartphones');

        $this->service->clearCache('smartphones');
    }

    public function test_clear_cache_for_all_types(): void
    {
        $this->cache->expects($this->exactly(17)) // 1 for all + 15 for types + 1 for hierarchy
            ->method('forget');

        $this->service->clearCache();
    }

    public function test_filter_config_to_array(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $config = $this->service->getFilterConfig('smartphones');
        $array = $config->toArray();

        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('icon', $array);
        $this->assertArrayHasKey('primary_filters', $array);
        $this->assertArrayHasKey('secondary_filters', $array);
        $this->assertArrayHasKey('sort_options', $array);
    }

    public function test_filter_config_from_array(): void
    {
        $data = [
            'type' => 'smartphones',
            'label' => 'Смартфоны',
            'icon' => 'smartphone',
            'primary_filters' => [],
            'secondary_filters' => [],
            'sort_options' => [],
        ];

        $config = FilterConfigDto::fromArray($data);

        $this->assertEquals('smartphones', $config->type);
        $this->assertEquals('Смартфоны', $config->label);
        $this->assertEquals('smartphone', $config->icon);
    }

    public function test_laptops_filter_config_has_specific_filters(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $config = $this->service->getFilterConfig('laptops');

        $this->assertEquals('laptops', $config->type);

        $screenSizeFilter = collect($config->secondaryFilters)->firstWhere('key', 'screen_size');
        $this->assertNotNull($screenSizeFilter);
        $this->assertEquals('checkbox', $screenSizeFilter['type']);
        $this->assertContains('13"', $screenSizeFilter['options']);
    }

    public function test_headphones_filter_config_has_noise_cancellation(): void
    {
        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $config = $this->service->getFilterConfig('headphones');

        $noiseFilter = collect($config->secondaryFilters)->firstWhere('key', 'noise_cancellation');
        $this->assertNotNull($noiseFilter);
        $this->assertEquals('checkbox', $noiseFilter['type']);
    }

    public function test_all_types_have_valid_filter_configs(): void
    {
        $this->cache->expects($this->exactly(15))
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        foreach (ElectronicsType::cases() as $type) {
            $config = $this->service->getFilterConfig($type->value);

            $this->assertNotNull($config);
            $this->assertEquals($type->value, $config->type);
            $this->assertEquals($type->getLabel(), $config->label);
            $this->assertEquals($type->getIcon(), $config->icon);
            $this->assertIsArray($config->primaryFilters);
            $this->assertIsArray($config->secondaryFilters);
            $this->assertIsArray($config->sortOptions);
        }
    }
}
