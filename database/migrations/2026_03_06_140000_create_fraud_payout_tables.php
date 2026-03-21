<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fraud_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id'); $table->integer('score');
            $table->json('features_checked'); $table->string('reason');
            $table->string('correlation_id')->index(); $table->timestamps();
        });

        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id(); $table->string('status'); $table->integer('total_items');
            $table->decimal('total_amount', 12, 2); $table->json('results');
            $table->string('correlation_id')->index(); $table->timestamps();
        });

        Schema::create('ml_model_versions', function (Blueprint $table) {
            $table->id(); $table->string('ver')->unique(); $table->integer('samples');
            $table->decimal('accuracy', 5, 4); $table->string('path');
            $table->string('correlation_id'); $table->timestamps();
        });
    }
};
