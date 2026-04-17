<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_delivery_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->boolean('delivery_enabled')->default(true);
            $table->decimal('min_delivery_radius_km', 5, 2)->default(1.0);
            $table->decimal('max_delivery_radius_km', 5, 2)->default(10.0);
            $table->decimal('base_delivery_fee_rub', 10, 2)->default(150.0);
            $table->decimal('per_km_fee_rub', 10, 2)->default(30.0);
            $table->decimal('free_delivery_threshold_rub', 12, 2)->default(2000.0);
            $table->json('delivery_hours');
            $table->json('excluded_zones')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->unique('restaurant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_delivery_settings');
    }
};
