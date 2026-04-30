<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_inventory', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('publisher_id')->constrained()->onDelete('cascade');
            $table->enum('inventory_type', ['short', 'banner', 'video', 'native'])->default('banner');
            $table->enum('placement', ['feed', 'story', 'search', 'interstitial'])->default('feed');
            $table->bigInteger('available_impressions')->default(0);
            $table->bigInteger('reserved_impressions')->default(0);
            $table->timestamp('available_from');
            $table->timestamp('available_until');
            $table->enum('status', ['available', 'reserved', 'sold_out'])->default('available');
            $table->json('targeting_restrictions')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['publisher_id', 'status']);
            $table->index(['inventory_type', 'placement']);
            $table->index(['status', 'available_from', 'available_until']);
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_inventory');
    }
};
