<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('geo_zones')) {
            Schema::create('geo_zones', function (Blueprint $table) {
                $table->comment('Географические зоны: доставки, обслуживания, фильтрации.');
                $table->id();
                $table->string('name')->comment('Название зоны');
                $table->decimal('lat', 10, 7)->comment('Широта центра');
                $table->decimal('lon', 10, 7)->comment('Долгота центра');
                $table->decimal('radius', 8, 2)->comment('Радиус зоны (км)');
                $table->jsonb('polygon')->nullable()->comment('Полигон границ');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->index(['lat', 'lon']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_zones');
    }
};
