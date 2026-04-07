<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deliveries')) {
            return;
        }

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('courier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->string('status')->default('pending')->index();
            $table->text('from_address');
            $table->text('to_address');
            $table->jsonb('payload')->nullable();
            $table->jsonb('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->comment('Deliveries table');
        });

        if (Schema::hasTable('delivery_routes')) {
            return;
        }

        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('delivery_id')->constrained('deliveries')->onDelete('cascade');
            $table->jsonb('route_data')->nullable();
            $table->unsignedInteger('estimated_time')->nullable()->comment('in minutes');
            $table->unsignedInteger('distance')->nullable()->comment('in meters');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->comment('Delivery routes from GeoLogistics');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_routes');
        Schema::dropIfExists('deliveries');
    }
};
