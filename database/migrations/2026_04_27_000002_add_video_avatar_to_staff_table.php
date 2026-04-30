<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add video avatar support to staff table.
     * CatVRF 2026 — PRODUCTION MANDATORY.
     *
     * Video avatar: 4-6 seconds, no sound, auto-generated from photo.
     */
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('video_avatar_url')->nullable()->after('photo_url');
            $table->string('video_avatar_path')->nullable()->after('video_avatar_url');
            $table->boolean('video_avatar_generated')->default(false)->after('video_avatar_path');
            $table->timestamp('video_avatar_generated_at')->nullable()->after('video_avatar_generated');
            $table->string('video_avatar_status')->default('pending')->after('video_avatar_generated_at'); // pending, processing, completed, failed
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn([
                'video_avatar_url',
                'video_avatar_path',
                'video_avatar_generated',
                'video_avatar_generated_at',
                'video_avatar_status',
            ]);
        });
    }
};
