<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_preparation_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('menu_item_type')->nullable(); // e.g., 'dish', 'drink', 'dessert'
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->string('menu_item_name');
            $table->foreignId('kitchen_station_id')->constrained('kitchen_stations')->onDelete('cascade');
            $table->unsignedInteger('preparation_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'menu_item_type', 'menu_item_id']);
            $table->index(['kitchen_station_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_preparation_times');
    }
};
