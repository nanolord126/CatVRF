<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apartments')) {
            return;
        }

        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_group_id')->nullable()->constrained();
            $table->uuid('uuid')->unique()->index();
            $table->string('title');
            $table->text('description');
            $table->text('address');
            $table->point('geo_point')->nullable();
            $table->integer('price_per_night');
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->integer('max_guests');
            $table->jsonb('amenities')->nullable();
            $table->jsonb('photos')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
