<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('beauty_salons')) {
            return;
        }

        Schema::create('beauty_salons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('address')->nullable();
            $table->point('geo_point')->nullable()->spatialIndex();
            $table->json('schedule')->comment('Operating hours: {"Mon": "10:00-19:00", ...}');
            $table->float('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_verified')->default(false)->index();
            $table->integer('commission_rate')->default(1400)->comment('in kopeks, 14%');
            $table->json('tags')->nullable()->comment('for analytics: ["migrated_from_dikidi", "priority_partner"]');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'business_group_id']);
            $table->comment('Beauty salons with geolocation and operational info');
        });

        Schema::create('beauty_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('full_name');
            $table->json('specialization')->comment('array of service types: ["haircut", "coloring", "massage"]');
            $table->integer('experience_years')->default(0);
            $table->float('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->string('photo_url')->nullable();
            $table->string('phone')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Masters/stylists/therapists at beauty salons');
        });

        Schema::create('beauty_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->nullable()->constrained('beauty_masters')->onDelete('set null')->index();
            $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes');
            $table->integer('price')->comment('in kopeks');
            $table->integer('commission_rate')->nullable()->comment('override salon commission if set');
            $table->json('consumables')->nullable()->comment('{"consumable_id": quantity, ...}');
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salon_id', 'is_active']);
            $table->comment('Beauty services offered by salons/masters');
        });

        Schema::create('beauty_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade')->index();
            $table->foreignId('master_id')->constrained('beauty_masters')->onDelete('cascade')->index();
            $table->foreignId('service_id')->constrained('beauty_services')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->dateTime('datetime_start')->index();
            $table->dateTime('datetime_end')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending')->index();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->index();
            $table->integer('price')->comment('in kopeks');
            $table->text('client_comment')->nullable();
            $table->string('correlation_id')->index()->nullable();
            $table->json('tags')->nullable();
            $table->dateTime('hold_until')->nullable()->comment('20-min hold expiry time');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'datetime_start', 'status']);
            $table->index(['master_id', 'datetime_start']);
            $table->index(['user_id', 'created_at']);
            $table->comment('Appointments with hold/release logic for 20-min reserve');
        });

        Schema::create('beauty_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('sku')->unique();
            $table->integer('current_stock')->default(0);
            $table->integer('hold_stock')->default(0)->comment('reserved stock');
            $table->integer('min_threshold')->default(10);
            $table->integer('max_threshold')->default(100);
            $table->integer('price')->comment('in kopeks');
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salon_id', 'current_stock']);
            $table->comment('Beauty products sold in salon (cosmetics, tools)');
        });

        Schema::create('beauty_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('sku')->unique();
            $table->integer('current_stock')->default(0);
            $table->integer('hold_stock')->default(0);
            $table->integer('min_threshold')->default(5);
            $table->integer('price_per_unit')->comment('in kopeks');
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salon_id', 'current_stock']);
            $table->comment('Consumables auto-deducted after service (gloves, foil, towels)');
        });

        Schema::create('beauty_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('salon_id')->nullable()->constrained('beauty_salons')->onDelete('set null')->index();
            $table->foreignId('master_id')->nullable()->constrained('beauty_masters')->onDelete('set null')->index();
            $table->foreignId('service_id')->nullable()->constrained('beauty_services')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('rating')->comment('1-5');
            $table->text('comment');
            $table->json('photos')->nullable()->comment('array of image URLs');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['salon_id', 'created_at']);
            $table->index(['master_id', 'created_at']);
            $table->comment('Reviews for salons, masters, services with photos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_reviews');
        Schema::dropIfExists('beauty_consumables');
        Schema::dropIfExists('beauty_products');
        Schema::dropIfExists('beauty_appointments');
        Schema::dropIfExists('beauty_services');
        Schema::dropIfExists('beauty_masters');
        Schema::dropIfExists('beauty_salons');
    }
};
