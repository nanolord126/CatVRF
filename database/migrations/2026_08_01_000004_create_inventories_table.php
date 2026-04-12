<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->nullable()->index();
            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);
            $table->integer('available')->virtualAs('quantity - reserved');
            $table->integer('min_stock_level')->default(0)->comment('Alert threshold');
            $table->integer('max_stock_level')->default(0)->comment('Maximum capacity');
            $table->decimal('cost_price', 14, 2)->nullable()->comment('Unit cost price');
            $table->decimal('wholesale_price', 14, 2)->nullable()->comment('B2B wholesale price');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('condition', ['new', 'refurbished', 'used', 'damaged'])->default('new');
            $table->string('location_in_warehouse')->nullable()->comment('Shelf/row/bin');
            $table->string('correlation_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id', 'batch_number']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['quantity', 'min_stock_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
