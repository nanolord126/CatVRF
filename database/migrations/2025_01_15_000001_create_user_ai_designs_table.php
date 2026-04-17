<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists (duplicate migration)
        if (Schema::hasTable('user_ai_designs')) {
            return;
        }

        Schema::create('user_ai_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->string('vertical')->index();
            $table->json('design_data');
            $table->string('correlation_id')->nullable()->index();
            $table->string('uuid')->unique();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'vertical']);
            $table->index(['tenant_id', 'vertical']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_designs');
    }
};
