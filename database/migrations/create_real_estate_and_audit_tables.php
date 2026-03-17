<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Недвижимость (Real Estate Core)
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->enum('type', ['apartment', 'land', 'commercial', 'business', 'rental']);
            $table->string('name')->index();
            $table->decimal('area', 12, 2)->index();
            $table->decimal('price', 15, 2)->index();
            $table->json('geo_data')->nullable(); // lat, lng, district
            $table->json('amenities')->nullable(); // wifi, parking, etc.
            $table->uuid('correlation_id')->index();
            $table->json('metadata')->nullable(); // for AI embeddings
            $table->timestamps();
        });

        // 2. Аудит действий 2026 (Audit Log v2)
        Schema::create('action_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action'); // create, update, delete, ai_query
            $table->string('model_type')->index();
            $table->unsignedBigInteger('model_id')->index();
            $table->json('payload')->nullable();
            $table->decimal('risk_score', 5, 2)->default(0); // ML Fraud Protection
            $table->uuid('correlation_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_audits');
        Schema::dropIfExists('properties');
    }
};
