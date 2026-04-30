<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_booking_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();
            $table->string('source'); // booking_com, ostrovok, airbnb, etc.
            $table->string('external_id')->unique(); // External booking ID from marketplace
            $table->string('external_confirmation_code')->nullable();
            $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->timestamp('synced_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->json('raw_data')->nullable(); // Store raw marketplace data for debugging
            $table->timestamps();

            $table->index(['source', 'external_id']);
            $table->index('sync_status');
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_booking_references');
    }
};
