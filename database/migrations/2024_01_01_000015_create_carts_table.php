<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['active', 'expired', 'ordered'])->default('active');
            $table->timestamp('reserved_until')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'seller_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('reserved_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
