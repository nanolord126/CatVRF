<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_intent_id')->nullable()->after('id')->index();
            $table->string('payment_url')->nullable()->after('provider_payment_id');
            $table->string('confirmation_url')->nullable()->after('payment_url');
            $table->string('client_secret')->nullable()->after('confirmation_url');
            $table->boolean('requires_action')->default(false)->after('three_ds_verified');
            $table->string('next_action')->nullable()->after('requires_action');
            $table->timestamp('expires_at')->nullable()->after('failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_intent_id',
                'payment_url',
                'confirmation_url',
                'client_secret',
                'requires_action',
                'next_action',
                'expires_at',
            ]);
        });
    }
};
