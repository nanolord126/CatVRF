<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Публичные страницы (Marketplace Landing Pages)
        Schema::create('marketplace_landings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // 'flowers', 'restaurants', 'vet-clinics'
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('category_filters')->nullable(); // JSON с фильтрами (гео, цена, рейтинг)
            $table->string('theme_color')->default('#3b82f6');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        // 2. Глобальный поиск (Search Metadata for Scout/Typesense)
        Schema::create('marketplace_search_index', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type'); // Модель (Product, Clinic, Restaurant)
            $table->unsignedBigInteger('searchable_id');
            $table->string('tenant_id');
            $table->string('title')->index();
            $table->text('content');
            $table->json('geo_point')->nullable(); // [lat, lng]
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->json('vector_embedding')->nullable(); // Поле для OpenAI Embeddings/Vector Search
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_search_index');
        Schema::dropIfExists('marketplace_landings');
    }
};

