<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create ad_impressions table for Advertising vertical
 *
 * Tracks ad impression events with cost calculation,
 * device fingerprinting, and fraud detection data.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('placement_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->ipAddress('ip_address')->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('device_fingerprint', 64)->nullable()->index();
            $table->string('referer', 500)->nullable();
            $table->unsignedInteger('cost')->default(0)->comment('Cost in cents');
            $table->enum('status', ['served', 'skipped', 'blocked', 'fraud'])->default('served')->index();
            $table->boolean('is_clicked')->default(false)->index();
            $table->timestamp('clicked_at')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->json('context')->nullable()->comment('Additional impression context');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('campaign_id')
                ->references('id')
                ->on('ad_campaigns')
                ->onDelete('cascade');

            $table->foreign('placement_id')
                ->references('id')
                ->on('ad_placements')
                ->onDelete('set null');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Composite indexes for analytics queries
            $table->index(['campaign_id', 'status'], 'ad_impressions_campaign_status_idx');
            $table->index(['campaign_id', 'created_at'], 'ad_impressions_campaign_time_idx');
            $table->index(['placement_id', 'status'], 'ad_impressions_placement_status_idx');
            $table->index(['ip_address', 'status'], 'ad_impressions_ip_status_idx');
            $table->index(['device_fingerprint', 'status'], 'ad_impressions_fingerprint_status_idx');
            $table->index(['created_at'], 'ad_impressions_created_at_idx');

            // Partitioning hint for high-volume tables
            $table->index(['campaign_id', 'created_at'], 'ad_impressions_partition_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_impressions');
    }
};
