<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses')) {
            return;
        }

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('instructor_id')->constrained('users');
            $table->string('title');
            $table->text('description');
            $table->integer('price');
            $table->integer('duration_hours');
            $table->string('level')->default('beginner');
            $table->boolean('is_published')->default(false);
            $table->string('thumbnail_url')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
