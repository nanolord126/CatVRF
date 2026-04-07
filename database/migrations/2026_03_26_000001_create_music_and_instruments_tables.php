<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for MusicAndInstruments vertical.
     */
    public function up(): void
    {
        // 1. Music Stores / Schools / Studios
        if (!Schema::hasTable('music_stores')) {
            Schema::create('music_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('business_group_id')->nullable()->index();
                $table->string('correlation_id')->nullable()->index();
                
                $table->string('name')->comment('Name of the music shop or school');
                $table->string('slug')->unique();
                $table->string('address');
                $table->json('geo_point')->nullable()->comment('Latitude and Longitude');
                $table->json('schedule')->nullable()->comment('Opening hours');
                $table->decimal('rating', 3, 2)->default(0);
                $table->integer('review_count')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->enum('type', ['shop', 'school', 'studio', 'mixed'])->default('mixed');
                
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Music stores, schools, and studios main table');
            });
        }

        // 2. Instruments
        if (!Schema::hasTable('music_instruments')) {
            Schema::create('music_instruments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('music_store_id')->index();
                $table->string('tenant_id')->index();
                $table->string('correlation_id')->nullable()->index();
                
                $table->string('name');
                $table->string('brand');
                $table->string('model');
                $table->enum('category', ['guitar', 'piano', 'drums', 'violin', 'brass', 'synth', 'folk', 'other']);
                $table->enum('condition', ['new', 'used', 'refurbished'])->default('new');
                $table->integer('price_cents')->comment('Price in kopecks');
                $table->integer('rental_price_cents')->nullable()->comment('Rental price per day in kopecks');
                $table->integer('stock')->default(0);
                $table->integer('hold_stock')->default(0);
                
                $table->jsonb('specifications')->nullable()->comment('Instrument specs like strings, keys, wood type');
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Inventory of musical instruments');
            });
        }

        // 3. Accessories & Consumables
        if (!Schema::hasTable('music_accessories')) {
            Schema::create('music_accessories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('music_store_id')->index();
                $table->string('tenant_id')->index();
                $table->string('correlation_id')->nullable()->index();
                
                $table->string('sku')->unique();
                $table->string('name');
                $table->string('type')->comment('Strings, Picks, Cables, Reeds, Drumsticks, etc.');
                $table->integer('price_cents');
                $table->integer('stock')->default(0);
                $table->integer('min_stock_threshold')->default(5);
                
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Accessories and consumables for instruments');
            });
        }

        // 4. Recording Studios / Rooms
        if (!Schema::hasTable('music_studios')) {
            Schema::create('music_studios', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('music_store_id')->index();
                $table->string('tenant_id')->index();
                $table->string('correlation_id')->nullable()->index();
                
                $table->string('name');
                $table->text('description');
                $table->integer('price_per_hour_cents');
                $table->integer('min_booking_hours')->default(1);
                $table->json('equipment')->nullable()->comment('Available equipment in the studio');
                $table->boolean('has_engineer')->default(false);
                
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('Recording studios and practice rooms');
            });
        }

        // 5. Music Lessons
        if (!Schema::hasTable('music_lessons')) {
            Schema::create('music_lessons', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('music_store_id')->index();
                $table->string('tenant_id')->index();
                $table->string('correlation_id')->nullable()->index();
                
                $table->string('teacher_name');
                $table->string('subject')->comment('Guitar, Vocals, Theory, etc.');
                $table->enum('level', ['beginner', 'intermediate', 'advanced', 'all'])->default('all');
                $table->integer('duration_minutes')->default(60);
                $table->integer('price_cents');
                $table->enum('format', ['offline', 'online', 'hybrid'])->default('offline');
                
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('Music lessons and teachers');
            });
        }

        // 6. Bookings (Studios & Lessons & Rentals)
        if (!Schema::hasTable('music_bookings')) {
            Schema::create('music_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('business_group_id')->nullable()->index();
                $table->string('correlation_id')->nullable()->index();
                
                $table->unsignedBigInteger('user_id')->index();
                $table->morphs('bookable'); // studio, lesson, instrument (rental)
                
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->integer('total_price_cents');
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
                $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'failed'])->default('unpaid');
                
                $table->jsonb('metadata')->nullable();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Unified booking table for Music vertical');
            });
        }

        // 7. Reviews
        if (!Schema::hasTable('music_reviews')) {
            Schema::create('music_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('correlation_id')->nullable()->index();
                
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('music_store_id')->index();
                $table->morphs('reviewable'); // instrument, teacher, studio
                
                $table->integer('rating')->unsigned();
                $table->text('comment');
                $table->jsonb('media')->nullable()->comment('Photos/Videos');
                $table->boolean('is_published')->default(true);
                
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('Reviews for music products and services');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('music_reviews');
        Schema::dropIfExists('music_bookings');
        Schema::dropIfExists('music_lessons');
        Schema::dropIfExists('music_studios');
        Schema::dropIfExists('music_accessories');
        Schema::dropIfExists('music_instruments');
        Schema::dropIfExists('music_stores');
    }
};


