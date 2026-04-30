<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('category'); // technical, soft, domain, language
            $table->text('description');
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'expert', 'master'])->default('beginner');
            $table->integer('proficiency')->default(0); // 0-100
            $table->timestamp('last_assessed')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('tenant_id');
            $table->index('category');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_skills');
    }
};
