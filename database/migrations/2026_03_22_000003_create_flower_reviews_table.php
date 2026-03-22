<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('flower_reviews')) {
            return;
        }

        Schema::create('flower_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('order_id')->constrained('flower_orders');
            $table->foreignId('user_id')->constrained();
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->jsonb('photos')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flower_reviews');
    }
};
