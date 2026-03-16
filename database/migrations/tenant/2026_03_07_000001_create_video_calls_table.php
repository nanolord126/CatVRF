<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: video calls table handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->string('room_id')->unique();
            $table->foreignId('caller_id')->constrained('users');
            $table->foreignId('receiver_id')->nullable()->constrained('users');
            $table->string('status')->default('initiated'); // initiated, active, ended
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('recording_path')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamps(); $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('video_calls'); }
};
