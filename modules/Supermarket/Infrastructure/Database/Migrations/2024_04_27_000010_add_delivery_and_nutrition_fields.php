<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add delivery tracking, nutritional info, and allergen fields to supermarket orders
     */
    public function up(): void
    {
        Schema::table('crm_supermarket_orders', function (Blueprint $table) {
            // Delivery Type
            $table->enum('delivery_type', ['courier', 'pickup'])->default('courier')->after('order_type');
            
            // Courier Information (for courier delivery)
            $table->unsignedBigInteger('courier_id')->nullable()->after('delivery_type');
            $table->string('courier_name')->nullable()->after('courier_id');
            $table->string('courier_phone')->nullable()->after('courier_name');
            $table->enum('courier_vehicle_type', ['car', 'bicycle', 'walk', 'scooter'])->nullable()->after('courier_phone');
            $table->json('delivery_route')->nullable()->after('courier_vehicle_type');
            
            // Courier Tracking
            $table->decimal('courier_location_lat', 10, 8)->nullable()->after('delivery_route');
            $table->decimal('courier_location_lng', 11, 8)->nullable()->after('courier_location_lat');
            $table->timestamp('courier_location_updated_at')->nullable()->after('courier_location_lng');
            $table->timestamp('courier_assigned_at')->nullable()->after('courier_location_updated_at');
            $table->timestamp('courier_arrived_at')->nullable()->after('courier_assigned_at');
            $table->timestamp('delivery_eta')->nullable()->after('courier_arrived_at');
            $table->integer('delivery_progress')->default(0)->after('delivery_eta');
            
            // Delivery Handoff
            $table->enum('handoff_method', ['in_hand', 'locker', 'doorstep'])->nullable()->after('delivery_progress');
            $table->string('delivery_photo_url')->nullable()->after('handoff_method');
            $table->string('client_signature_url')->nullable()->after('delivery_photo_url');
            $table->timestamp('handoff_at')->nullable()->after('client_signature_url');
            
            // Pickup Information (for pickup)
            $table->string('pickup_zone')->nullable()->after('handoff_at');
            $table->string('locker_number')->nullable()->after('pickup_zone');
            $table->string('qr_code')->nullable()->after('locker_number');
            $table->timestamp('pickup_ready_at')->nullable()->after('qr_code');
            $table->timestamp('pickup_window_start')->nullable()->after('pickup_ready_at');
            $table->timestamp('pickup_window_end')->nullable()->after('pickup_window_start');
            $table->timestamp('customer_arrived_at')->nullable()->after('pickup_window_end');
            $table->unsignedBigInteger('pickup_operator_id')->nullable()->after('customer_arrived_at');
            $table->timestamp('pickup_started_at')->nullable()->after('pickup_operator_id');
            $table->timestamp('id_verified_at')->nullable()->after('pickup_started_at');
            $table->string('pickup_photo_url')->nullable()->after('id_verified_at');
            
            // Processing Information
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('pickup_photo_url');
            $table->unsignedBigInteger('processing_operator_id')->nullable()->after('warehouse_id');
            $table->timestamp('processing_started_at')->nullable()->after('processing_operator_id');
            $table->integer('items_picked_count')->default(0)->after('processing_started_at');
            $table->json('items_missing')->nullable()->after('items_picked_count');
            $table->boolean('quality_check_passed')->default(false)->after('items_missing');
            $table->boolean('expiry_check_passed')->default(false)->after('quality_check_passed');
            $table->decimal('cold_chain_temperature', 5, 2)->nullable()->after('expiry_check_passed');
            $table->string('storage_location')->nullable()->after('cold_chain_temperature');
            $table->decimal('storage_temperature', 5, 2)->nullable()->after('storage_location');
            
            // Nutritional Information (aggregated for order)
            $table->integer('total_calories')->default(0)->after('storage_temperature');
            $table->decimal('total_proteins', 8, 2)->default(0)->after('total_calories');
            $table->decimal('total_fats', 8, 2)->default(0)->after('total_proteins');
            $table->decimal('total_carbs', 8, 2)->default(0)->after('total_fats');
            $table->decimal('total_fiber', 8, 2)->default(0)->after('total_carbs');
            $table->decimal('total_sugar', 8, 2)->default(0)->after('total_fiber');
            $table->integer('total_sodium')->default(0)->after('total_sugar');
            
            // Item Metadata
            $table->integer('items_count')->default(0)->after('total_sodium');
            $table->decimal('items_weight', 8, 3)->default(0)->after('items_count');
            $table->decimal('items_volume', 8, 3)->default(0)->after('items_weight');
            $table->boolean('contains_perishable')->default(false)->after('items_volume');
            $table->boolean('contains_cold_chain')->default(false)->after('contains_perishable');
            
            // Allergen Warnings
            $table->json('allergen_warnings')->nullable()->after('contains_cold_chain');
            $table->json('allergen_details')->nullable()->after('allergen_warnings');
            
            // Ratings and Feedback
            $table->integer('delivery_rating')->nullable()->after('allergen_details');
            $table->integer('pickup_rating')->nullable()->after('delivery_rating');
            $table->integer('courier_rating')->nullable()->after('pickup_rating');
            $table->text('delivery_feedback')->nullable()->after('courier_rating');
            $table->text('pickup_feedback')->nullable()->after('delivery_feedback');
            $table->json('delivery_issues')->nullable()->after('pickup_feedback');
            $table->json('pickup_issues')->nullable()->after('delivery_issues');
            
            // Performance Metrics
            $table->integer('fulfillment_duration')->nullable()->after('pickup_issues');
            $table->boolean('on_time_delivery')->default(true)->after('fulfillment_duration');
            $table->integer('queue_wait_time')->nullable()->after('on_time_delivery');
            
            // Indexes
            $table->index('delivery_type');
            $table->index('courier_id');
            $table->index('courier_assigned_at');
            $table->index('pickup_ready_at');
            $table->index('pickup_window_start');
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('crm_supermarket_orders', function (Blueprint $table) {
            // Drop added columns in reverse order
            $table->dropIndex(['warehouse_id']);
            $table->dropIndex(['pickup_window_start']);
            $table->dropIndex(['pickup_ready_at']);
            $table->dropIndex(['courier_assigned_at']);
            $table->dropIndex(['courier_id']);
            $table->dropIndex(['delivery_type']);
            
            $table->dropColumn([
                'delivery_type',
                'courier_id',
                'courier_name',
                'courier_phone',
                'courier_vehicle_type',
                'delivery_route',
                'courier_location_lat',
                'courier_location_lng',
                'courier_location_updated_at',
                'courier_assigned_at',
                'courier_arrived_at',
                'delivery_eta',
                'delivery_progress',
                'handoff_method',
                'delivery_photo_url',
                'client_signature_url',
                'handoff_at',
                'pickup_zone',
                'locker_number',
                'qr_code',
                'pickup_ready_at',
                'pickup_window_start',
                'pickup_window_end',
                'customer_arrived_at',
                'pickup_operator_id',
                'pickup_started_at',
                'id_verified_at',
                'pickup_photo_url',
                'warehouse_id',
                'processing_operator_id',
                'processing_started_at',
                'items_picked_count',
                'items_missing',
                'quality_check_passed',
                'expiry_check_passed',
                'cold_chain_temperature',
                'storage_location',
                'storage_temperature',
                'total_calories',
                'total_proteins',
                'total_fats',
                'total_carbs',
                'total_fiber',
                'total_sugar',
                'total_sodium',
                'items_count',
                'items_weight',
                'items_volume',
                'contains_perishable',
                'contains_cold_chain',
                'allergen_warnings',
                'allergen_details',
                'delivery_rating',
                'pickup_rating',
                'courier_rating',
                'delivery_feedback',
                'pickup_feedback',
                'delivery_issues',
                'pickup_issues',
                'fulfillment_duration',
                'on_time_delivery',
                'queue_wait_time',
            ]);
        });
    }
};
