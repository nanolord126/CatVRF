<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restaurant_reservations')) {
            return;
        }

        Schema::create('restaurant_reservations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->date('reservation_date')->index();
            $table->time('reservation_time');
            $table->unsignedInteger('party_size')->default(1);
            $table->string('status', 50)->default('pending')->comment('pending, confirmed, cancelled, completed, no_show');
            $table->json('special_requests')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('confirmation_code', 50)->nullable()->unique();
            $table->boolean('is_confirmed')->default(false);
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['tenant_id', 'reservation_date']);
            $table->index(['tenant_id', 'status']);
        });

        \Illuminate\Support\Facades\DB::statement(
            "COMMENT ON TABLE restaurant_reservations IS 'Бронирования столов в ресторанах (вертикаль Restaurant)'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_reservations');
    }
};
