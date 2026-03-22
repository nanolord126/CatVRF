<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apartment_bookings')) {
            return;
        }

        Schema::create('apartment_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('guests_count');
            $table->integer('total_price');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'apartment_id', 'check_in_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_bookings');
    }
};
