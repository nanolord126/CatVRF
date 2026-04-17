<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->text('special_instructions')->nullable();
            $table->json('add_ons')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'menu_item_id']);
            $table->index('uuid');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('restaurant_orders')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('restaurant_menu_items')->onDelete('set null');
        });

        Schema::table('restaurant_order_items', function (Blueprint $table) {
            $table->comment('Restaurant order items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_items');
    }
};
