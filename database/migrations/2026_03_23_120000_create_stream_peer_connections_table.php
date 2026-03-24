<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_peer_connections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Unique identifier for tracking');
            $table->foreignId('stream_id')
                ->constrained('events')
                ->cascadeOnDelete()
                ->comment('Parent stream/event reference');
            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Multi-tenant scoping');
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Viewer or broadcaster peer');
            $table->string('peer_id', 255)
                ->unique()
                ->index()
                ->comment('RTCPeerConnection ID (unique per connection)');
            $table->json('ice_candidates')
                ->nullable()
                ->comment('Array of ICE candidates {candidate, sdpMLineIndex, sdpMid}');
            $table->string('local_sdp', 10000)
                ->nullable()
                ->comment('Local SDP offer/answer');
            $table->string('remote_sdp', 10000)
                ->nullable()
                ->comment('Remote SDP offer/answer');
            $table->enum('status', ['connecting', 'connected', 'failed', 'closed'])
                ->default('connecting')
                ->index()
                ->comment('Peer connection state');
            $table->enum('connection_type', ['p2p', 'sfu'])
                ->default('p2p')
                ->comment('Mesh topology: peer-to-peer or SFU');
            $table->string('correlation_id', 36)
                ->nullable()
                ->index()
                ->comment('Request correlation ID for audit trail');
            $table->json('tags')
                ->nullable()
                ->comment('Analytics and filtering tags');
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['stream_id', 'tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'created_at']);
            $table->unique(['stream_id', 'peer_id']);
        });

        // Add comment to table
        Schema::table('stream_peer_connections', function (Blueprint $table) {
            $table->comment('WebRTC peer-to-peer connections for live streaming (mesh topology)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_peer_connections');
    }
};
