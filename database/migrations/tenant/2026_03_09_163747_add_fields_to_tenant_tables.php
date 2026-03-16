<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: tenant table fields handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->string('type'); // daily, weekly
            $table->date('report_date');
            $table->json('data'); // Revenue, orders, AI text
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'report_date']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('timezone')->default('Europe/Moscow');
            $table->json('report_emails')->nullable(); // Array of strings
        });
        
        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable();
            $table->boolean('is_public')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reports');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'report_emails']);
        });
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_public']);
        });
    }
};
