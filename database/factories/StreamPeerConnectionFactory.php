<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\StreamPeerConnection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StreamPeerConnection>
 */
final class StreamPeerConnectionFactory extends Factory
{
    protected $model = StreamPeerConnection::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'stream_id' => Event::factory(),
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'peer_id' => 'peer_' . Str::random(20),
            'ice_candidates' => [],
            'local_sdp' => $this->generateSdp(),
            'remote_sdp' => null,
            'status' => $this->faker->randomElement(['connecting', 'connected', 'failed']),
            'connection_type' => $this->faker->randomElement(['p2p', 'sfu']),
            'correlation_id' => Str::uuid(),
            'tags' => [
                'ip' => $this->faker->ipv4(),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            ],
        ];
    }

    /**
     * Set status to connected
     */
    public function connected(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'connected',
        ]);
    }

    /**
     * Set connection type to SFU
     */
    public function sfu(): self
    {
        return $this->state(fn (array $attributes) => [
            'connection_type' => 'sfu',
        ]);
    }

    /**
     * Generate mock SDP (Session Description Protocol)
     */
    private function generateSdp(): string
    {
        return 'v=0' . "\r\n" .
            'o=- ' . time() . ' 2 IN IP4 127.0.0.1' . "\r\n" .
            's=-' . "\r\n" .
            't=0 0' . "\r\n" .
            'a=group:BUNDLE 0 1' . "\r\n" .
            'a=msid-semantic: WMS stream' . "\r\n" .
            'm=audio 1 RTP/SAVPF 111' . "\r\n" .
            'a=rtcp:9 IN IP4 0.0.0.0' . "\r\n" .
            'a=ice-ufrag:abcd' . "\r\n" .
            'a=ice-pwd:abcdefghijklmnopqrstuvwxyz' . "\r\n" .
            'a=fingerprint:sha-256 00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00' . "\r\n" .
            'a=setup:actpass' . "\r\n" .
            'a=mid:0' . "\r\n" .
            'a=sendrecv' . "\r\n" .
            'a=rtcp-mux' . "\r\n";
    }
}
