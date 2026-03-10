<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('beauty_products', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->enum('type', ['inventory', 'cosmetics', 'perfumery']);
            $table->decimal('price', 15, 2);
            $table->integer('stock')->default(0);
            $table->json('images')->nullable();
            $table->timestamps();
        });

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('code', 12)->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 5, 2)->default(3.00);
            $table->enum('status', ['pending', 'active', 'used', 'expired'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('activated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('active_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->string('ip', 45);
            $table->text('user_agent');
            $table->string('location')->nullable();
            $table->string('browser')->nullable();
            $table->timestamp('last_active_at');
            $table->string('pending_auth_code', 6)->nullable();
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users');
            $table->foreignId('referred_id')->constrained('users');
            $table->string('type')->default('individual'); // individual, business
            $table->decimal('milestone_turnover', 15, 2)->default(0);
            $table->boolean('bonus_paid_50k')->default(false);
            $table->timestamps();
        });

        Schema::create('ai_assistant_chats', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('user_id')->constrained();
            $table->enum('category', ['legal_accounting', 'marketing_content', 'market_analysis']);
            $table->json('messages');
            $table->integer('request_count')->default(0);
            $table->timestamp('quota_reset_at');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ai_assistant_chats');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('active_devices');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('beauty_products');
    }
};
