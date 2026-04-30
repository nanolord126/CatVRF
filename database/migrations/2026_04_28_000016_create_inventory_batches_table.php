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
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('batch_number');
            $table->date('manufacture_date');
            $table->date('expiry_date');
            $table->integer('initial_quantity');
            $table->integer('current_quantity');
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->string('storage_location')->nullable();
            $table->enum('status', ['active', 'expiring_soon', 'expired', 'quarantine'])->default('active');
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['inventory_item_id', 'batch_number']);
            $table->index(['inventory_item_id', 'expiry_date', 'current_quantity', 'status']);
            $table->index(['tenant_id', 'expiry_date']);
            $table->index(['tenant_id', 'status', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
