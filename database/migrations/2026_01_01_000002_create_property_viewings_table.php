<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('property_viewings')) {
            return;
        }

        Schema::create('property_viewings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('agent_id')->nullable()->index();
            $table->dateTime('scheduled_at')->index();
            $table->dateTime('held_at')->nullable();
            $table->dateTime('hold_expires_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->enum('status', ['pending', 'held', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending')->index();
            $table->boolean('is_b2b')->default(false)->index();
            $table->string('webrtc_room_id')->nullable()->index();
            $table->boolean('faceid_verified')->default(false);
            $table->string('cancellation_reason')->nullable();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'property_id', 'status'], 'property_viewings_tenant_property_status_idx');
            $table->index(['tenant_id', 'user_id', 'scheduled_at'], 'property_viewings_tenant_user_scheduled_idx');
            $table->index(['property_id', 'scheduled_at', 'status'], 'property_viewings_property_scheduled_status_idx');
        });

        Schema::table('property_viewings', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('business_group_id')->references('id')->on('business_groups')->onDelete('set null');
            $table->foreign('property_id')->references('id')->on('real_estate_properties')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('real_estate_agents')->onDelete('set null');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE property_viewings COMMENT = 'Бронирования просмотров недвижимости с hold слотами, WebRTC и FaceID'"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_viewings');
    }
};
