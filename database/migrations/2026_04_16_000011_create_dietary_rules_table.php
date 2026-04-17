<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dietary_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('rule_name');
            $table->string('diet_type')->default('standard');
            $table->integer('calories_min')->default(1500);
            $table->integer('calories_max')->default(2500);
            $table->decimal('protein_min_g', 8, 2)->default(50.0);
            $table->decimal('protein_max_g', 8, 2)->default(200.0);
            $table->decimal('fat_min_g', 8, 2)->default(30.0);
            $table->decimal('fat_max_g', 8, 2)->default(100.0);
            $table->decimal('carbs_min_g', 8, 2)->default(100.0);
            $table->decimal('carbs_max_g', 8, 2)->default(400.0);
            $table->json('allergens')->nullable();
            $table->json('preferred_ingredients')->nullable();
            $table->json('excluded_ingredients')->nullable();
            $table->json('meal_schedule')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dietary_rules');
    }
};
