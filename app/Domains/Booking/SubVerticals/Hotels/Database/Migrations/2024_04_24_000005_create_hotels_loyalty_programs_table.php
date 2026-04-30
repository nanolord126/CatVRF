<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels_loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained('hotels_venues')->onDelete('cascade');
            $table->string('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('level', ['bronze', 'silver', 'gold', 'platinum', 'corporate']);
            $table->integer('points_per_night')->default(10);
            $table->decimal('points_to_rubles_rate', 10, 4)->default(0.01);
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'venue_id', 'is_active']);
            $table->index(['tenant_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels_loyalty_programs');
    }
};
