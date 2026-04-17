<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->string('vertical', 50)->index();
            $table->string('design_type', 50)->index();
            $table->json('design_data');
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'vertical']);
            $table->index(['tenant_id', 'vertical', 'design_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_designs');
    }
};
