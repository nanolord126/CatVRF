<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_return_policies', function (Blueprint $table) {
            $table->id();
            $table->string('sub_vertical');
            $table->string('vertical')->default('supermarket');
            $table->integer('max_days')->default(3);
            $table->json('allowed_reasons');
            $table->boolean('cold_chain_only_defect')->default(false);
            $table->boolean('requires_photo')->default(false);
            $table->boolean('requires_temperature')->default(false);
            $table->integer('max_refund_percent')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['sub_vertical', 'vertical']);
            $table->index(['sub_vertical', 'is_active']);
            $table->index('vertical');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_return_policies');
    }
};
