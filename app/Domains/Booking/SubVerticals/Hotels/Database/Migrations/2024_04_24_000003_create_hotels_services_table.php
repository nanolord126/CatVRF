<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained('hotels_venues')->onDelete('cascade');
            $table->string('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'breakfast', 'transfer', 'spa', 'minibar', 'laundry',
                'room_service', 'parking', 'excursion', 'additional_bed',
                'late_checkout', 'early_checkin'
            ]);
            $table->decimal('base_price', 10, 2);
            $table->string('currency', 3)->default('RUB');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_optional')->default(true);
            $table->string('icon')->nullable();
            $table->json('pricing_rules')->nullable();
            $table->json('availability_rules')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('max_quantity_per_booking')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'venue_id', 'is_available']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels_services');
    }
};
