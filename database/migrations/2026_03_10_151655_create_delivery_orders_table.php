<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->text('pickup_address');
            $table->text('delivery_address');
            $table->decimal('distance', 10, 2)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('correlation_id')->nullable()->index();
            $table->enum('status', ['pending', 'assigned', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};

