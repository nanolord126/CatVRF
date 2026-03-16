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
        Schema::create('behavioral_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_type');
            $table->string('vertical')->nullable();
            $table->string('target_id')->nullable();
            $table->json('payload')->nullable();
            $table->decimal('monetary_value', 15, 2)->default(0);
            $table->uuid('correlation_id')->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            
            // Индексы для ClickHouse/Elasticsearch экспорта
            $table->index(['user_id', 'event_type']);
        });

        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('segment_type'); // rfm, churn_risk, vip, interest
            $table->string('value');
            $table->integer('score')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'segment_type']);
        });

        Schema::create('marketing_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('trigger_type'); // geofence, cross_sell, dynamic_price
            $table->string('offer_id');
            $table->string('channel'); // push, sms, email
            $table->string('status'); // sent, clicked, converted
            $table->uuid('correlation_id')->index();
            $table->decimal('revenue_impact', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_automation_logs');
        Schema::dropIfExists('customer_segments');
        Schema::dropIfExists('behavioral_events');
    }
};
