<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_client_favorites', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'user_id', 'type']);
            $table->index(['tenant_id', 'driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_client_favorites');
    }
};
