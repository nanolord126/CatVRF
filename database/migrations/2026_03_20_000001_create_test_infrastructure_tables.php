<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт таблицы для тестов: taxi_rides, wishlist_items, обновляет payment_transactions.
 * CANON 2026: idempotent (if hasTable), correlation_id, uuid, tenant_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── taxi_rides ────────────────────────────────────────────────────────
        if (! Schema::hasTable('taxi_rides')) {
            Schema::create('taxi_rides', function (Blueprint $table) {
                $table->comment('Поездки такси — CANON 2026');
                $table->id();
                $table->string('uuid', 36)->nullable()->unique()->index();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('driver_id')->index();
                $table->unsignedBigInteger('passenger_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable();
                $table->string('vehicle_class')->default('economy');
                $table->decimal('pickup_lat', 10, 7)->default(0);
                $table->decimal('pickup_lng', 10, 7)->default(0);
                $table->decimal('dropoff_lat', 10, 7)->default(0);
                $table->decimal('dropoff_lng', 10, 7)->default(0);
                $table->decimal('distance_km', 8, 2)->default(0);
                $table->unsignedBigInteger('fare_amount')->default(0)->comment('Копейки');
                $table->decimal('surge_multiplier', 4, 2)->default(1.0);
                $table->string('status')->default('pending')->index();
                $table->string('cancellation_reason')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->json('tags')->nullable();
                $table->timestamps();
            });
        }

        // ─── wishlist_items ────────────────────────────────────────────────────
        if (! Schema::hasTable('wishlist_items')) {
            Schema::create('wishlist_items', function (Blueprint $table) {
                $table->comment('Список желаний пользователей — CANON 2026');
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('item_type', 64)->index();
                $table->unsignedBigInteger('item_id')->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'item_type', 'item_id'], 'wishlist_unique');
            });
        }

        // ─── payment_transactions — добавить недостающие колонки ──────────────
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_transactions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            }
            if (! Schema::hasColumn('payment_transactions', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }
            if (! Schema::hasColumn('payment_transactions', 'wallet_id')) {
                $table->unsignedBigInteger('wallet_id')->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'idempotency_key')) {
                $table->string('idempotency_key', 255)->nullable()->unique();
            }
            if (! Schema::hasColumn('payment_transactions', 'provider')) {
                $table->string('provider', 64)->nullable()->default('tinkoff');
            }
            if (! Schema::hasColumn('payment_transactions', 'provider_code')) {
                $table->string('provider_code', 64)->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'provider_payment_id')) {
                $table->string('provider_payment_id', 255)->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'currency')) {
                $table->string('currency', 3)->nullable()->default('RUB');
            }
            if (! Schema::hasColumn('payment_transactions', 'hold')) {
                $table->boolean('hold')->default(false);
            }
            if (! Schema::hasColumn('payment_transactions', 'hold_amount')) {
                $table->unsignedBigInteger('hold_amount')->default(0);
            }
            if (! Schema::hasColumn('payment_transactions', 'authorized_at')) {
                $table->timestamp('authorized_at')->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'captured_at')) {
                $table->timestamp('captured_at')->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'failed_at')) {
                $table->timestamp('failed_at')->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'fraud_score')) {
                $table->decimal('fraud_score', 5, 4)->nullable()->default(0);
            }
            if (! Schema::hasColumn('payment_transactions', 'fraud_ml_version')) {
                $table->string('fraud_ml_version', 32)->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'three_ds_required')) {
                $table->boolean('three_ds_required')->default(false);
            }
            if (! Schema::hasColumn('payment_transactions', 'three_ds_verified')) {
                $table->boolean('three_ds_verified')->default(false);
            }
            if (! Schema::hasColumn('payment_transactions', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'tags')) {
                $table->json('tags')->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (! Schema::hasColumn('payment_transactions', 'device_fingerprint')) {
                $table->string('device_fingerprint', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('taxi_rides');
    }
};
