<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publisher_sub_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('publisher_id')->constrained('publishers')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled'])->default('pending');
            $table->json('permissions');
            $table->decimal('revenue_share', 5, 4)->default(0.5000);
            $table->bigInteger('monthly_quota')->default(1000000);
            $table->timestamp('verified_at')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['publisher_id', 'status']);
            $table->index('status');
            $table->index('email');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publisher_sub_accounts');
    }
};
