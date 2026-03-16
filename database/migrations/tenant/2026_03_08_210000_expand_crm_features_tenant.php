<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: CRM features expansion handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
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
