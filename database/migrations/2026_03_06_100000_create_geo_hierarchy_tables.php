<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', fn(Blueprint $t) => $this->geoColumns($t));
        }
        if (!Schema::hasTable('regions')) {
            Schema::create('regions', fn(Blueprint $t) => $this->geoColumns($t, 'country_id'));
        }
        if (!Schema::hasTable('districts')) {
            Schema::create('districts', fn(Blueprint $t) => $this->geoColumns($t, 'region_id'));
        }
        if (!Schema::hasTable('cities')) {
            Schema::create('cities', fn(Blueprint $t) => $this->geoColumns($t, 'district_id'));
        }
        if (!Schema::hasTable('areas')) {
            Schema::create('areas', fn(Blueprint $t) => $this->geoColumns($t, 'city_id'));
        }
    }

    private function geoColumns(Blueprint $t, ?string $parentId = null): void {
        $t->id(); $t->string('correlation_id')->nullable()->index(); $t->string('name')->index(); $t->string('code')->nullable();
        if ($parentId) $t->foreignId($parentId)->constrained()->cascadeOnDelete();
        $t->timestamps(); $t->softDeletes();
    }
};
