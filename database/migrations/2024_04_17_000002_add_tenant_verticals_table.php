<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create tenant_verticals table for vertical configuration per tenant
     */
    public function up(): void
    {
        Schema::create('tenant_verticals', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('vertical'); // Medical, Beauty, Food, etc.
            $table->boolean('is_enabled')->default(true);
            $table->json('configuration')->nullable();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['tenant_id', 'vertical']);
            $table->index(['tenant_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_verticals');
    }
};
