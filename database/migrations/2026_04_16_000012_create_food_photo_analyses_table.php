<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_photo_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('photo_hash')->unique();
            $table->json('composition');
            $table->json('nutritional_info');
            $table->integer('estimated_weight_grams');
            $table->integer('estimated_calories');
            $table->decimal('confidence_score', 3, 2);
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['tenant_id', 'user_id']);
            $table->index('photo_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_photo_analyses');
    }
};
