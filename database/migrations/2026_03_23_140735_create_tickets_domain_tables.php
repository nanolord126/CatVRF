<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tickets_events')) return;

        Schema::create('tickets_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('cascade');
            $table->string('title')->comment('Название события');
            $table->text('description')->nullable();
            $table->string('type')->index()->comment('concert, tour, quest, billiard, etc');
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->nullable();
            $table->jsonb('settings')->nullable()->comment('Схемы залов, лимиты');
            $table->string('status')->default('active')->index();
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Таблица событий, билетов, туров и квестов');
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('event_id')->constrained('tickets_events')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('price_kopeks')->default(0);
            $table->string('seat_number')->nullable();
            $table->string('status')->default('available')->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->comment('Таблица купленных или забронированных билетов');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('tickets_events');
    }
};
