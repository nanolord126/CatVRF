<?php declare(strict_types=1);

namespace Tests\Unit\RealEstate;

use Tests\TestCase;
use Modules\RealEstate\Services\AI\RealEstateDesignConstructorService;
use Illuminate\Support\Str;

final class RealEstateDesignConstructorServiceTest extends TestCase
{
    private RealEstateDesignConstructorService $aiConstructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiConstructor = app(RealEstateDesignConstructorService::class);
    }

    public function test_analyze_property_data(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('analyzePropertyData');
        $method->setAccessible(true);

        $data = [
            'area' => 100,
            'rooms' => 3,
            'property_type' => 'apartment',
            'city' => 'Москва',
        ];

        $analysis = $method->invoke($this->aiConstructor, $data);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('property_type', $analysis);
        $this->assertArrayHasKey('area', $analysis);
        $this->assertArrayHasKey('rooms', $analysis);
        $this->assertArrayHasKey('style_recommendation', $analysis);
        $this->assertArrayHasKey('layout_optimization', $analysis);
        $this->assertArrayHasKey('lighting_analysis', $analysis);
        $this->assertArrayHasKey('material_suggestions', $analysis);
        $this->assertArrayHasKey('smart_home_features', $analysis);
        $this->assertArrayHasKey('energy_efficiency', $analysis);
    }

    public function test_determine_interior_style(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('determineInteriorStyle');
        $method->setAccessible(true);

        $data = [
            'ceiling_height' => 3.5,
        ];

        $style = $method->invoke($this->aiConstructor, $data);

        $this->assertIsArray($style);
        $this->assertArrayHasKey('primary_style', $style);
        $this->assertArrayHasKey('alternative_styles', $style);
        $this->assertArrayHasKey('color_palette', $style);
        $this->assertArrayHasKey('confidence', $style['primary_style']);
    }

    public function test_optimize_layout(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('optimizeLayout');
        $method->setAccessible(true);

        $layout = $method->invoke($this->aiConstructor, 100, 3, 'apartment');

        $this->assertIsArray($layout);
        $this->assertArrayHasKey('room_distribution', $layout);
        $this->assertArrayHasKey('open_plan_suggestion', $layout);
        $this->assertArrayHasKey('storage_optimization', $layout);
        $this->assertArrayHasKey('space_efficiency_score', $layout);
    }

    public function test_analyze_lighting(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('analyzeLighting');
        $method->setAccessible(true);

        $data = [
            'windows' => 3,
            'orientation' => 'south',
            'area' => 100,
        ];

        $lighting = $method->invoke($this->aiConstructor, $data);

        $this->assertIsArray($lighting);
        $this->assertArrayHasKey('natural_light_score', $lighting);
        $this->assertArrayHasKey('artificial_lighting_plan', $lighting);
        $this->assertArrayHasKey('smart_lighting_recommended', $lighting);
        $this->assertGreaterThanOrEqual(0, $lighting['natural_light_score']);
        $this->assertLessThanOrEqual(1, $lighting['natural_light_score']);
    }

    public function test_suggest_materials(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('suggestMaterials');
        $method->setAccessible(true);

        $materials = $method->invoke($this->aiConstructor, 'apartment', 'Москва');

        $this->assertIsArray($materials);
        $this->assertArrayHasKey('flooring', $materials);
        $this->assertArrayHasKey('walls', $materials);
        $this->assertArrayHasKey('kitchen', $materials);
        $this->assertArrayHasKey('bathroom', $materials);
    }

    public function test_suggest_smart_home_features(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('suggestSmartHomeFeatures');
        $method->setAccessible(true);

        $features = $method->invoke($this->aiConstructor, 100, 'apartment');

        $this->assertIsArray($features);
        $this->assertArrayHasKey('lighting_automation', $features);
        $this->assertArrayHasKey('climate_control', $features);
        $this->assertArrayHasKey('security_system', $features);
        $this->assertArrayHasKey('energy_monitoring', $features);
    }

    public function test_calculate_energy_efficiency(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('calculateEnergyEfficiency');
        $method->setAccessible(true);

        $data = [
            'insulation' => 'enhanced',
            'window_type' => 'triple_glazed',
            'heating' => 'heat_pump',
            'area' => 100,
        ];

        $efficiency = $method->invoke($this->aiConstructor, $data);

        $this->assertIsArray($efficiency);
        $this->assertArrayHasKey('overall_score', $efficiency);
        $this->assertArrayHasKey('insulation_rating', $efficiency);
        $this->assertArrayHasKey('recommended_improvements', $efficiency);
        $this->assertArrayHasKey('estimated_savings_per_year', $efficiency);
        $this->assertGreaterThanOrEqual(0, $efficiency['overall_score']);
        $this->assertLessThanOrEqual(1, $efficiency['overall_score']);
    }

    public function test_calculate_renovation_cost(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('calculateRenovationCost');
        $method->setAccessible(true);

        $analysis = [
            'area' => 100,
            'style_recommendation' => ['name' => 'Современный минимализм'],
        ];

        $cost = $method->invoke($this->aiConstructor, $analysis);

        $this->assertIsArray($cost);
        $this->assertArrayHasKey('total_estimated', $cost);
        $this->assertArrayHasKey('breakdown', $cost);
        $this->assertArrayHasKey('contingency', $cost);
        $this->assertArrayHasKey('timeline_weeks', $cost);
        $this->assertGreaterThan(0, $cost['total_estimated']);
    }

    public function test_generate_color_palette(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('generateColorPalette');
        $method->setAccessible(true);

        $palette = $method->invoke($this->aiConstructor, 'Современный минимализм');

        $this->assertIsArray($palette);
        $this->assertArrayHasKey('primary', $palette);
        $this->assertArrayHasKey('secondary', $palette);
        $this->assertArrayHasKey('accent', $palette);
        $this->assertCount(5, $palette);
    }

    public function test_estimate_renovation_timeline(): void
    {
        $reflection = new \ReflectionClass($this->aiConstructor);
        $method = $reflection->getMethod('estimateRenovationTimeline');
        $method->setAccessible(true);

        $analysis = [
            'area' => 100,
            'rooms' => 3,
        ];

        $timeline = $method->invoke($this->aiConstructor, $analysis);

        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('total_weeks', $timeline);
        $this->assertArrayHasKey('phases', $timeline);
        $this->assertArrayHasKey('estimated_completion', $timeline);
        $this->assertGreaterThan(0, $timeline['total_weeks']);
    }
}
