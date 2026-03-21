<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── fraud_attempts ──────────────────────────────────────────────────
        if (! Schema::hasTable('fraud_attempts')) {
            Schema::create('fraud_attempts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('operation_type')->index();
                $table->string('ip_address')->nullable();
                $table->string('device_fingerprint')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->float('ml_score')->default(0);
                $table->string('ml_version')->default('v1-rules');
                $table->string('decision')->default('allow'); // allow|review|block
                $table->text('features_json')->nullable();
                $table->timestamp('blocked_at')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
                $table->comment('ML fraud scoring attempts log');
            });
        }

        // ─── inventory_items ─────────────────────────────────────────────────
        // Drop and recreate to ensure correct schema (old migration has incompatible schema)
        Schema::dropIfExists('inventory_items');
        Schema::create('inventory_items', function (Blueprint $table): void {
                $table->id();
                $table->string('uuid')->nullable()->unique();
                $table->string('tenant_id')->nullable()->index();
                $table->string('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('sku')->nullable();
                $table->string('name')->nullable();
                $table->integer('current_stock')->default(0);
                $table->integer('hold_stock')->default(0);
                $table->integer('min_stock_threshold')->default(0);
                $table->integer('max_stock_threshold')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->json('tags')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamps();
                $table->comment('Inventory stock levels per tenant');
            });

        // ─── stock_movements ─────────────────────────────────────────────────
        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('inventory_item_id')->index();
                $table->string('type'); // in|out|adjust|reserve|release|correction
                $table->integer('quantity'); // signed
                $table->string('reason')->nullable();
                $table->string('source_type')->nullable(); // order|appointment|manual|import|refund
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->comment('All stock movement records');
            });
        }

        // ─── enrollments (for Courses vertical) ──────────────────────────────
        if (! Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('course_id')->index();
                $table->string('status')->default('active'); // active|completed|cancelled
                $table->float('progress')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->comment('Course enrollments for users');
            });
        }

        // ─── category_preference column in users ─────────────────────────────
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'category_preference')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('category_preference')->nullable()->after('tags');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('fraud_attempts');
        if (Schema::hasColumn('users', 'category_preference')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('category_preference');
            });
        }
    }
};
