<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_couriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->string('vehicle_type')->default('bicycle');
            $table->string('vehicle_number')->nullable();
            $table->decimal('restaurant_lat', 10, 8)->nullable();
            $table->decimal('restaurant_lon', 11, 8)->nullable();
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('current_order_id')->nullable()->constrained('restaurant_orders')->onDelete('set null');
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->integer('completed_deliveries')->default(0);
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['tenant_id', 'is_available', 'is_active']);
            $table->index(['current_latitude', 'current_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_couriers');
    }
};
