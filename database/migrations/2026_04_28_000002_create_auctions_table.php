<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['forward', 'dutch', 'sealed_bid'])->default('forward');
            $table->enum('status', ['upcoming', 'active', 'closed', 'cancelled'])->default('upcoming');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->bigInteger('starting_price')->default(0);
            $table->bigInteger('current_price')->default(0);
            $table->bigInteger('reserve_price')->default(0);
            $table->foreignId('inventory_id')->nullable()->constrained('ad_inventory')->onDelete('set null');
            $table->json('bid_history')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'start_at', 'end_at']);
            $table->index('inventory_id');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
