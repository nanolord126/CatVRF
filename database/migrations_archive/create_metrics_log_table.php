<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics_log', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->json('data')->nullable();
            $table->float('duration_ms')->default(0)->index();
            $table->string('status')->default('success')->index();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('method')->nullable();
            $table->string('endpoint')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->float('memory_mb')->default(0);
            $table->integer('cpu_percent')->default(0)->nullable();
            $table->string('environment')->default('production');
            $table->string('version')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['type', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics_log');
    }
};
