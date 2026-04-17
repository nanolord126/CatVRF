<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('real_estate_bookings')) {
            return;
        }

        Schema::create('real_estate_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id', 255)->nullable()->index();
            $table->uuid('property_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('viewing_slot')->index();
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'expired', 'refunded'])->default('pending')->index();
            $table->json('deal_score')->nullable();
            $table->decimal('fraud_score', 4, 4)->default(0.0000);
            $table->string('idempotency_key', 255)->nullable()->unique();
            $table->boolean('is_b2b')->default(false)->index();
            $table->dateTime('hold_until')->nullable()->index();
            $table->boolean('face_id_verified')->default(false);
            $table->boolean('blockchain_verified')->default(false);
            $table->string('webrtc_room_id', 255)->nullable();
            $table->decimal('original_price', 14, 2)->nullable();
            $table->decimal('dynamic_discount', 14, 2)->nullable();
            $table->decimal('escrow_amount', 14, 2)->default(0);
            $table->json('commission_split')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('property_id')->references('id')->on('real_estate_properties')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index(['property_id', 'viewing_slot']);
            $table->index(['user_id', 'status']);
            $table->index(['hold_until', 'status']);
        });

        // SQLite doesn't support table comments via ALTER TABLE
        if (config('database.default') !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE real_estate_bookings COMMENT = 'Бронирования просмотров недвижимости с AI-скорингом и escrow'"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_bookings');
    }
};
