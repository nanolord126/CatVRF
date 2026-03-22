<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('flower_orders')) {
            return;
        }

        Schema::create('flower_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_group_id')->nullable()->constrained();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('bouquet_id')->nullable()->constrained('bouquets');
            $table->foreignId('perfume_id')->nullable()->constrained('perfumes');
            $table->integer('quantity')->default(1);
            $table->integer('total_price');
            $table->string('status')->default('pending');
            $table->text('delivery_address');
            $table->timestamp('delivery_at')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flower_orders');
    }
};
