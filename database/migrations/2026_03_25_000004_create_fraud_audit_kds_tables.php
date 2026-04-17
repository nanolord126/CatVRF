<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Low stock alerts table - skip if already exists
        if (!Schema::hasTable('low_stock_alerts')) {
            Schema::create('low_stock_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->integer('current_stock');
                $table->integer('min_threshold');
                $table->enum('status', ['pending', 'sent', 'acknowledged'])->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->string('correlation_id')->index();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Low stock notifications');
            });
        }

        // Referral qualification checks table - skip if already exists
        if (!Schema::hasTable('referral_qualification_checks')) {
            Schema::create('referral_qualification_checks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referral_id')->constrained('referrals')->onDelete('cascade');
                $table->dateTime('scheduled_for');
                $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
                $table->boolean('qualified')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('correlation_id')->index();
                $table->timestamps();
                $table->index(['referral_id', 'status']);
                $table->comment('Referral qualification checks');
            });
        }

        // Fraud attempts table - skip if already exists
        if (!Schema::hasTable('fraud_attempts')) {
            Schema::create('fraud_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->enum('operation_type', [
                    'payment_init', 'card_bind', 'payout', 'rating_submit',
                    'referral_claim', 'promo_apply', 'order_create'
                ]);
                $table->string('ip_address');
                $table->string('device_fingerprint')->nullable();
                $table->string('correlation_id')->index();
                $table->float('ml_score')->default(0);
                $table->string('ml_version')->nullable();
                $table->json('features')->nullable();
                $table->enum('decision', ['allow', 'block', 'review'])->default('allow');
                $table->timestamp('blocked_at')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'user_id', 'ml_score']);
                $table->comment('Fraud detection attempts');
            });
        }

        // Fraud model versions table - skip if already exists
        if (!Schema::hasTable('fraud_model_versions')) {
            Schema::create('fraud_model_versions', function (Blueprint $table) {
                $table->id();
                $table->string('version')->unique();
                $table->timestamp('trained_at');
                $table->float('accuracy')->nullable();
                $table->float('precision')->nullable();
                $table->float('recall')->nullable();
                $table->float('f1_score')->nullable();
                $table->float('auc_roc')->nullable();
                $table->string('file_path');
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->comment('ML fraud detection models');
            });
        }

        // KDS orders table - skip if already exists
        if (!Schema::hasTable('kds_orders')) {
            Schema::create('kds_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_order_id')->constrained('restaurant_orders')->onDelete('cascade');
                $table->integer('queue_position')->default(0);
                $table->enum('status', ['pending', 'preparing', 'ready', 'cancelled'])->default('pending');
                $table->timestamp('prepared_at')->nullable();
                $table->timestamp('ready_at')->nullable();
                $table->string('correlation_id')->index();
                $table->timestamps();
                $table->index(['status', 'queue_position']);
                $table->comment('Kitchen Display System orders');
            });
        }

        // Audit logs table - skip if already exists
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->string('action');
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->json('changes')->nullable();
                $table->string('correlation_id')->index();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'user_id', 'action']);
                $table->comment('Audit trail for all operations');
            });
        }

        // API keys table for service-to-service communication - skip if already exists
        if (!Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->string('key')->unique();
                $table->string('secret_hash');
                $table->json('scopes')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
                $table->index(['key']);
                $table->comment('API keys for service authentication');
            });
        }

        // Rate limit records table - skip if already exists
        if (!Schema::hasTable('rate_limit_records')) {
            Schema::create('rate_limit_records', function (Blueprint $table) {
                $table->id();
                $table->string('key')->index();
                $table->integer('attempts')->default(0);
                $table->timestamp('reset_at');
                $table->timestamps();
                $table->index(['key', 'reset_at']);
                $table->comment('Rate limiting tracking');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limit_records');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('kds_orders');
        Schema::dropIfExists('fraud_model_versions');
        Schema::dropIfExists('fraud_attempts');
        Schema::dropIfExists('referral_qualification_checks');
        Schema::dropIfExists('low_stock_alerts');
    }
};


