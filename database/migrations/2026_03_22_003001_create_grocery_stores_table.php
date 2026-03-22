<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grocery_stores')) {
            return;
        }

        Schema::create('grocery_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_group_id')->nullable()->constrained();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('address');
            $table->point('geo_point')->nullable();
            $table->string('store_type');
            $table->string('kitchen_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'store_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_stores');
    }
};
