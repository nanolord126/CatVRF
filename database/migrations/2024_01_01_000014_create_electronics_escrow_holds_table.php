<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronics_escrow_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('payment_id', 64)->unique()->index();
            $table->bigInteger('amount_kopecks');
            $table->enum('status', ['held', 'released', 'cancelled'])->default('held')->index();
            $table->timestamp('release_date')->nullable();
            $table->string('release_reason')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['order_id', 'payment_id']);
            $table->index(['status', 'release_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronics_escrow_holds');
    }
};
