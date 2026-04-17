<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->onDelete('set null');
            $table->enum('order_type', ['dine_in', 'pickup', 'delivery'])->default('dine_in');
            $table->json('delivery_address')->nullable();
            $table->json('delivery_coordinates')->nullable();
            $table->decimal('delivery_radius_km', 5, 2)->nullable();
            $table->integer('estimated_delivery_minutes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('delivery_fee', 10, 2)->default(0.00);
            $table->decimal('service_fee', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_transaction_id')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'served', 'picked_up', 'delivering', 'delivered', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->json('special_requests')->nullable();
            $table->boolean('is_split_payment')->default(false);
            $table->integer('split_parts')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'restaurant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['restaurant_id', 'table_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_orders');
    }
};
