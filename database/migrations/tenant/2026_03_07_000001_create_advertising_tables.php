<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: advertising tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $t->id(); $t->string('tenant_id')->index(); $t->string('name');
            $t->decimal('budget', 12, 2)->default(0); $t->string('vertical');
            $t->boolean('is_active')->default(1); $t->string('erid')->nullable();
            $t->date('start_date')->nullable(); $t->date('end_date')->nullable();
            $t->string('correlation_id')->index()->nullable(); $t->timestamps();
        });

        Schema::create('ad_creatives', function (Blueprint $t) {
            $t->id(); $t->foreignId('campaign_id')->constrained('ad_campaigns')->onDelete('cascade');
            $t->string('title'); $t->text('content')->nullable();
            $t->string('link'); $t->string('type')->default('banner');
            $t->string('erid')->nullable(); $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ad_creatives'); Schema::dropIfExists('ad_campaigns');
    }
};
