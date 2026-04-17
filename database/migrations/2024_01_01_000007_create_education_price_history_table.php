<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_price_history', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('original_price_kopecks');
            $table->integer('adjusted_price_kopecks');
            $table->decimal('discount_percent', 5, 2);
            $table->string('adjustment_reason', 255);
            $table->json('factors');
            $table->boolean('is_flash_sale')->default(false);
            $table->boolean('is_corporate')->default(false);
            $table->timestamp('valid_until')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'course_id']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['course_id', 'valid_until']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_price_history');
    }
};
