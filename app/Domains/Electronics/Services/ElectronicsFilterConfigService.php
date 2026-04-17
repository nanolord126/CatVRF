<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use App\Domains\Electronics\DTOs\FilterConfigDto;
use App\Domains\Electronics\Enums\ElectronicsType;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;

final readonly class ElectronicsFilterConfigService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private Cache $cache,
    ) {
    }

    /**
     * Get all electronics types with their filter configurations
     */
    public function getAllTypes(): Collection
    {
        return $this->cache->remember('electronics_types_all', now()->addSeconds(self::CACHE_TTL), function () {
            return collect(ElectronicsType::cases())
                ->map(fn (ElectronicsType $type) => [
                    'value' => $type->value,
                    'label' => $type->getLabel(),
                    'icon' => $type->getIcon(),
                ]);
        });
    }

    /**
     * Get filter configuration for a specific type
     */
    public function getFilterConfig(string $type): ?FilterConfigDto
    {
        try {
            $electronicsType = ElectronicsType::from($type);
        } catch (\ValueError $e) {
            return null;
        }

        return $this->cache->remember(
            "electronics_filter_config_{$type}",
            now()->addSeconds(self::CACHE_TTL),
            fn () => $electronicsType->getFilterConfig()
        );
    }

    /**
     * Get filter configurations for multiple types
     */
    public function getFilterConfigs(array $types): Collection
    {
        return collect($types)
            ->map(fn (string $type) => $this->getFilterConfig($type))
            ->filter();
    }

    /**
     * Get popular types based on product count
     */
    public function getPopularTypes(int $limit = 6): Collection
    {
        return $this->cache->remember('electronics_types_popular', now()->addSeconds(self::CACHE_TTL), function () use ($limit) {
            // TODO: Implement actual popularity based on product count
            // For now, return first N types
            return collect(ElectronicsType::cases())
                ->take($limit)
                ->map(fn (ElectronicsType $type) => [
                    'value' => $type->value,
                    'label' => $type->getLabel(),
                    'icon' => $type->getIcon(),
                ]);
        });
    }

    /**
     * Get search patterns for a specific type
     */
    public function getSearchPatterns(string $type): array
    {
        $config = $this->getFilterConfig($type);
        
        if (!$config) {
            return [];
        }

        return [
            'type' => $config->type,
            'label' => $config->label,
            'patterns' => $this->extractSearchPatterns($config),
        ];
    }

    /**
     * Extract searchable patterns from filter configuration
     */
    private function extractSearchPatterns(FilterConfigDto $config): array
    {
        $patterns = [];

        // Extract primary filter patterns
        foreach ($config->primaryFilters as $filter) {
            if ($filter['type'] === 'checkbox' && isset($filter['options'])) {
                foreach ($filter['options'] as $option) {
                    $patterns[] = [
                        'type' => $filter['key'],
                        'value' => $option,
                        'weight' => 'high',
                    ];
                }
            }
        }

        // Extract secondary filter patterns
        foreach ($config->secondaryFilters as $filter) {
            if ($filter['type'] === 'checkbox' && isset($filter['options'])) {
                foreach ($filter['options'] as $option) {
                    $patterns[] = [
                        'type' => $filter['key'],
                        'value' => $option,
                        'weight' => 'medium',
                    ];
                }
            }
        }

        return $patterns;
    }

    /**
     * Get type-specific search suggestions
     */
    public function getTypeSearchSuggestions(string $type, string $query, int $limit = 10): array
    {
        $config = $this->getFilterConfig($type);
        
        if (!$config) {
            return [];
        }

        $suggestions = [];
        $queryLower = strtolower($query);

        // Search through filter options
        foreach ($config->primaryFilters as $filter) {
            if ($filter['type'] === 'checkbox' && isset($filter['options'])) {
                foreach ($filter['options'] as $option) {
                    if (str_contains(strtolower($option), $queryLower)) {
                        $suggestions[] = [
                            'type' => $filter['key'],
                            'label' => $filter['label'],
                            'value' => $option,
                            'category' => 'primary',
                        ];
                    }
                }
            }
        }

        foreach ($config->secondaryFilters as $filter) {
            if ($filter['type'] === 'checkbox' && isset($filter['options'])) {
                foreach ($filter['options'] as $option) {
                    if (str_contains(strtolower($option), $queryLower)) {
                        $suggestions[] = [
                            'type' => $filter['key'],
                            'label' => $filter['label'],
                            'value' => $option,
                            'category' => 'secondary',
                        ];
                    }
                }
            }
        }

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Get type hierarchy for navigation
     */
    public function getTypeHierarchy(): array
    {
        return $this->cache->remember('electronics_types_hierarchy', now()->addSeconds(self::CACHE_TTL), function () {
            return [
                'mobile' => [
                    'label' => 'Мобильные устройства',
                    'types' => [
                        ElectronicsType::SMARTPHONES->value,
                        ElectronicsType::TABLETS->value,
                        ElectronicsType::SMARTWATCHES->value,
                        ElectronicsType::WEARABLE->value,
                    ],
                ],
                'computers' => [
                    'label' => 'Компьютеры',
                    'types' => [
                        ElectronicsType::LAPTOPS->value,
                        ElectronicsType::NETWORKING->value,
                        ElectronicsType::ACCESSORIES->value,
                    ],
                ],
                'audio_video' => [
                    'label' => 'Аудио и видео',
                    'types' => [
                        ElectronicsType::HEADPHONES->value,
                        ElectronicsType::AUDIO->value,
                        ElectronicsType::TV->value,
                        ElectronicsType::CAMERAS->value,
                    ],
                ],
                'gaming' => [
                    'label' => 'Игры',
                    'types' => [
                        ElectronicsType::GAMING->value,
                    ],
                ],
                'smart_home' => [
                    'label' => 'Умный дом',
                    'types' => [
                        ElectronicsType::HOME_AUTOMATION->value,
                    ],
                ],
                'auto' => [
                    'label' => 'Автоэлектроника',
                    'types' => [
                        ElectronicsType::CAR_ELECTRONICS->value,
                    ],
                ],
                'appliances' => [
                    'label' => 'Бытовая техника',
                    'types' => [
                        ElectronicsType::APPLIANCES->value,
                    ],
                ],
            ];
        });
    }

    /**
     * Clear filter configuration cache
     */
    public function clearCache(string $type = null): void
    {
        if ($type) {
            $this->cache->forget("electronics_filter_config_{$type}");
        } else {
            $this->cache->forget('electronics_types_all');
            $this->cache->forget('electronics_types_popular');
            $this->cache->forget('electronics_types_hierarchy');
            
            foreach (ElectronicsType::cases() as $enumType) {
                $this->cache->forget("electronics_filter_config_{$enumType->value}");
            }
        }
    }

    /**
     * Validate filter values against type configuration
     */
    public function validateFilterValues(string $type, array $filters): array
    {
        $config = $this->getFilterConfig($type);
        
        if (!$config) {
            return ['valid' => false, 'errors' => ['Invalid type']];
        }

        $errors = [];
        $validFilters = [];

        // Validate primary filters
        foreach ($config->primaryFilters as $filterConfig) {
            $key = $filterConfig['key'];
            if (isset($filters[$key])) {
                $validation = $this->validateFilterValue($filterConfig, $filters[$key]);
                if (!$validation['valid']) {
                    $errors[$key] = $validation['errors'];
                } else {
                    $validFilters[$key] = $filters[$key];
                }
            }
        }

        // Validate secondary filters
        foreach ($config->secondaryFilters as $filterConfig) {
            $key = $filterConfig['key'];
            if (isset($filters[$key])) {
                $validation = $this->validateFilterValue($filterConfig, $filters[$key]);
                if (!$validation['valid']) {
                    $errors[$key] = $validation['errors'];
                } else {
                    $validFilters[$key] = $filters[$key];
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'valid_filters' => $validFilters,
        ];
    }

    /**
     * Validate a single filter value
     */
    private function validateFilterValue(array $filterConfig, mixed $value): array
    {
        $type = $filterConfig['type'];
        $errors = [];

        switch ($type) {
            case 'checkbox':
                if (!is_array($value)) {
                    $errors[] = 'Must be an array';
                } else {
                    $validOptions = $filterConfig['options'] ?? [];
                    foreach ($value as $v) {
                        if (!in_array($v, $validOptions, true)) {
                            $errors[] = "Invalid option: {$v}";
                        }
                    }
                }
                break;

            case 'range':
                if (is_array($value)) {
                    if (isset($value['min']) && isset($filterConfig['min'])) {
                        if ($value['min'] < $filterConfig['min']) {
                            $errors[] = "Min value cannot be less than {$filterConfig['min']}";
                        }
                    }
                    if (isset($value['max']) && isset($filterConfig['max'])) {
                        if ($value['max'] > $filterConfig['max']) {
                            $errors[] = "Max value cannot be greater than {$filterConfig['max']}";
                        }
                    }
                }
                break;

            default:
                // No validation for other types
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
