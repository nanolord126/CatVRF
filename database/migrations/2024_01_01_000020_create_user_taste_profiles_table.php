<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_taste_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('category_preferences')->nullable();
            $table->json('price_range')->nullable();
            $table->json('brand_affinities')->nullable();
            $table->float('behavioral_score')->default(0.0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'user_id']);
            $table->index('behavioral_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_taste_profiles');
    }
};
