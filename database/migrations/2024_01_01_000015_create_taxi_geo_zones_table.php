<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_geo_zones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('type');
            $table->json('polygon')->nullable();
            $table->float('center_latitude')->nullable();
            $table->float('center_longitude')->nullable();
            $table->float('radius_meters')->nullable();
            $table->float('base_price_multiplier')->default(1.0);
            $table->integer('min_price_kopeki')->default(15000);
            $table->integer('max_price_kopeki')->default(500000);
            $table->boolean('surge_enabled')->default(false);
            $table->float('surge_multiplier_default')->default(1.0);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_geo_zones');
    }
};
