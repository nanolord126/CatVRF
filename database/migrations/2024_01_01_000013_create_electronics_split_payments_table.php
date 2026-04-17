<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronics_split_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('payment_id', 64)->unique()->index();
            $table->bigInteger('total_amount_kopecks');
            $table->json('payment_sources');
            $table->boolean('escrow_enabled')->default(false);
            $table->timestamp('escrow_release_date')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'escrow_enabled']);
            $table->index(['order_id', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronics_split_payments');
    }
};
