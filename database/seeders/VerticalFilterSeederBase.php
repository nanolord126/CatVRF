<?php

namespace Database\Seeders;

use App\Models\Filter;
use App\Models\FilterValue;
use Illuminate\Database\Seeder;

abstract class VerticalFilterSeederBase extends Seeder
{
    protected function createFilters(string $vertical, array $data): void
    {
        foreach ($data as $filterName => $config) {
            $filter = Filter::updateOrCreate(
                ['vertical' => $vertical, 'name' => $filterName],
                ['type' => $config['type'], 'unit' => $config['unit'] ?? null]
            );

            if (isset($config['values']) && is_array($config['values'])) {
                foreach ($config['values'] as $value) {
                    FilterValue::updateOrCreate(
                        ['filter_id' => $filter->id, 'value' => $value['value']],
                        ['label' => $value['label']]
                    );
                }
            }
        }
    }
}
