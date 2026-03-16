<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: settlement documents table handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->string('type'); // invoice, act, upd
            $table->string('number')->unique();
            $table->date('document_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('draft'); // draft, sent, signed, cancelled
            $table->string('file_path')->nullable();
            $table->string('signed_file_path')->nullable();
            $table->json('meta')->nullable();
            $table->uuid('correlation_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_documents');
    }
};
