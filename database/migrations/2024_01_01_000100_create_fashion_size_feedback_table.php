<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fashion_size_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
            $table->string('recommended_size');
            $table->enum('feedback', ['perfect', 'too_small', 'too_large']);
            $table->timestamps();
            
            $table->index(['user_id', 'tenant_id']);
            $table->index(['product_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_size_feedback');
    }
};
