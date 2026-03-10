<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->json('profile_data')->nullable(); // clothing_size, shoe_size, etc.
            $table->json('embeddings')->nullable(); // Vector representations
        });

        Schema::create('construction_projects', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('tenant_id');
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->decimal('budget', 15, 2)->default(0); $table->timestamps();
        });

        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id(); $table->string('number'); $table->string('tenant_id');
            $table->string('type'); // OSAGO, KASKO
            $table->decimal('premium_amount', 15, 2)->default(0);
            $table->date('expires_at'); $table->timestamps();
        });

        Schema::create('promo_campaigns', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('tenant_id');
            $table->string('type'); // B2G1, discount, coupon
            $table->json('rules'); $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
