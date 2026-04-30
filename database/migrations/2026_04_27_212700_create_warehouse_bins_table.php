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
        Schema::create('warehouse_bins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('zone_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('coordinates')->nullable();
            $table->integer('capacity')->default(100);
            $table->integer('current_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('zone_id')->references('id')->on('warehouse_zones')->onDelete('cascade');
            $table->index(['zone_id', 'is_active']);
            $table->index(['warehouse_id', 'code']);
            $table->unique(['zone_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_bins');
    }
};
