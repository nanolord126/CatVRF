<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('real_estate_agents')) {
            return;
        }

        Schema::create('real_estate_agents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('full_name', 255);
            $table->string('license_number', 100)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->decimal('rating', 3, 1)->default(5.0);
            $table->unsignedInteger('deals_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Stores assigned_property_ids and other metadata');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active'], 'real_estate_agents_tenant_active_idx');
        });

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE real_estate_agents COMMENT = 'Агенты по недвижимости — привязаны к tenant'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_agents');
    }
};
