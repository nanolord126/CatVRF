<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hotels - Room Cleaning
        Schema::create('hotel_room_cleanings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->string('cleaning_type'); // 'checkout', 'express', 'standard', 'deep'
            $table->integer('priority')->default(3);
            $table->string('status')->default('pending'); // pending, in_progress, completed, needs_rework
            $table->timestamp('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->boolean('checklist_completed')->default(false);
            $table->json('checklist_items')->nullable();
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('supervisor_approved')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->integer('quality_score')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'hotel_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['hotel_id', 'scheduled_at']);
            $table->index('scheduled_at');
        });

        // Restaurant - Order Fulfillment
        Schema::create('restaurant_order_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade');
            $table->string('order_type'); // 'b2b' or 'b2c'
            $table->foreignId('kitchen_station_id')->nullable()->constrained('restaurant_kitchen_stations')->onDelete('set null');
            $table->foreignId('assigned_chef_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_waiter_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, in_progress, ready, served
            $table->integer('priority')->default(3);
            $table->timestamp('preparation_started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->json('items');
            $table->text('special_instructions')->nullable();
            $table->json('allergies')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('quality_check')->default(false);
            $table->integer('customer_rating')->nullable();
            $table->text('delay_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['tenant_id', 'order_type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['restaurant_id', 'status']);
            $table->index(['order_type', 'status']);
        });

        // Kitchen Stations
        Schema::create('restaurant_kitchen_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // 'hot', 'cold', 'grill', 'bar', 'dessert'
            $table->boolean('is_active')->default(true);
            $table->integer('queue_length')->default(0);
            $table->integer('capacity')->default(5);
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('uuid')->unique();
            $table->timestamps();

            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['restaurant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_kitchen_stations');
        Schema::dropIfExists('restaurant_order_fulfillments');
        Schema::dropIfExists('hotel_room_cleanings');
    }
};
