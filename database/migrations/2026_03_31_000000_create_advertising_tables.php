<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ad_campaigns')) {
            return;
        }

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('status')->default('draft')->index();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->unsignedBigInteger('budget')->comment('In cents');
            $table->unsignedBigInteger('spent')->default(0)->comment('In cents');
            $table->string('pricing_model')->comment('cpc, cpm');
            $table->jsonb('targeting_criteria')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->comment('Advertising Campaigns');
        });

        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->onDelete('cascade');
            $table->unsignedBigInteger('placement_id')->index(); // In a real app, this would be a foreign key
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->ipAddress('ip_address');
            $table->string('device_fingerprint');
            $table->unsignedInteger('cost')->comment('In cents');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->comment('Ad Impressions Log');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_impressions');
        Schema::dropIfExists('ad_campaigns');
    }
};
