<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $this->schema->create('ad_campaigns', function (Blueprint $t) {
            $t->id(); $t->string('tenant_id')->index(); $t->string('name');
            $t->decimal('budget', 12, 2)->default(0); $t->string('vertical');
            $t->boolean('is_active')->default(1); $t->string('erid')->nullable();
            $t->date('start_date')->nullable(); $t->date('end_date')->nullable();
            $t->string('correlation_id')->index()->nullable(); $t->timestamps();
        });

        $this->schema->create('ad_creatives', function (Blueprint $t) {
            $t->id(); $t->foreignId('campaign_id')->constrained('ad_campaigns')->onDelete('cascade');
            $t->string('title'); $t->text('content')->nullable();
            $t->string('link'); $t->string('type')->default('banner');
            $t->string('erid')->nullable(); $t->timestamps();
        });
    }

    public function down(): void {
        $this->schema->dropIfExists('ad_creatives'); $this->schema->dropIfExists('ad_campaigns');
    }
};
