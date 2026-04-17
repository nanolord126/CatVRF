<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_quota_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('resource_type', 50)->index();
            $table->bigInteger('usage')->default(0);
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'resource_type', 'recorded_at'], 'tenant_resource_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_quota_usage');
    }
};
