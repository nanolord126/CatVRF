<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fashion_brand_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('fashion_brands')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->decimal('adjustment_factor', 5, 3)->default(1.000);
            $table->integer('sample_size')->default(0);
            $table->timestamps();
            
            $table->unique(['brand_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_brand_adjustments');
    }
};
