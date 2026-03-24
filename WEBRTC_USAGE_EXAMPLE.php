<?php

declare(strict_types=1);

/**
 * EXAMPLE USAGE: WebRTC Live Streaming in Tickets Vertical
 * 
 * Integration with Tickets/Events vertical for live broadcasting
 * Features:
 * - Event streaming with P2P mesh
 * - Automatic SFU switch for large events (>15 viewers)
 * - Pinned products overlay via data channels
 * - NFT gift integration
 * 
 * Place this in app/Http/Controllers/Tickets/ or similar
 */

namespace App\Http\Controllers\Tickets;

use App\Models\Event;
use App\Models\StreamPeerConnection;
use App\Services\MeshService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EventStreamController extends Controller
{
    public function __construct(
        private readonly MeshService $mesh,
    ) {}

    /**
     * Show live stream player for event
     */
    public function show(Request $request, Event $event): View
    {
        // Check if user can access this event
        $this->authorize('view', $event);

        // Get current peer count
        $peerCount = StreamPeerConnection::forStream($event->id)
            ->connected()
            ->count();

        // Get event details with related data
        $eventData = [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'is_live' => $event->is_live,
            'topology' => $event->topology ?? 'p2p',
            'peer_count' => $peerCount,
            'pinned_products' => $event->pinnedProducts()->get(),
            'allow_nft_gifts' => $event->allow_nft_gifts,
        ];

        return view('tickets.stream', [
            'event' => $event,
            'eventData' => $eventData,
        ]);
    }

    /**
     * Get live stream metrics (for dashboard)
     */
    public function metrics(Request $request, Event $event): JsonResponse
    {
        $peers = StreamPeerConnection::forStream($event->id)->get();

        return response()->json([
            'stream_id' => $event->id,
            'total_peers' => $peers->count(),
            'connected_peers' => $peers->where('status', 'connected')->count(),
            'failed_peers' => $peers->where('status', 'failed')->count(),
            'topology' => $event->topology ?? 'p2p',
            'peer_details' => $peers->map(function ($peer) {
                return [
                    'peer_id' => $peer->peer_id,
                    'status' => $peer->status,
                    'connection_type' => $peer->connection_type,
                    'user' => $peer->user->name,
                    'ice_candidates_count' => count($peer->ice_candidates ?? []),
                    'created_at' => $peer->created_at,
                ];
            }),
        ]);
    }

    /**
     * Get pinned products for overlay
     */
    public function pinnedProducts(Request $request, Event $event): JsonResponse
    {
        $products = $event->pinnedProducts()
            ->with(['images'])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->images->first()?->url,
                    'url' => route('marketplace.product.show', $product),
                ];
            });

        return response()->json(['products' => $products]);
    }

    /**
     * Send NFT gift (data channel message)
     */
    public function sendNftGift(Request $request, Event $event): JsonResponse
    {
        if (!$event->allow_nft_gifts) {
            return response()->json(['error' => 'NFT gifts disabled'], 403);
        }

        $validated = $request->validate([
            'nft_id' => 'required|string',
            'from_peer_id' => 'required|string',
            'to_user_id' => 'nullable|integer',
            'message' => 'nullable|string|max:500',
        ]);

        // Broadcast gift notification via data channel
        // This would be handled by frontend via RTCDataChannel
        
        broadcast(
            new \App\Events\Stream\NftGiftSent(
                $event->id,
                $validated['nft_id'],
                auth()->user(),
                $validated['to_user_id'],
                $validated['message']
            )
        );

        return response()->json([
            'status' => 'gift_sent',
            'nft_id' => $validated['nft_id'],
        ]);
    }

    /**
     * Get viewer analytics (for event organizer)
     */
    public function analytics(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $peers = StreamPeerConnection::forStream($event->id)
            ->with(['user'])
            ->get();

        return response()->json([
            'total_viewers' => $peers->count(),
            'connected_now' => $peers->where('status', 'connected')->count(),
            'topology_current' => $event->topology ?? 'p2p',
            'switch_history' => $event->topologySwitchHistory ?? [],
            'top_locations' => $peers
                ->groupBy('tags.ip')
                ->map(fn ($group) => $group->count())
                ->sortDesc()
                ->take(10),
            'connection_quality' => [
                'excellent' => $peers->where('status', 'connected')->count(),
                'good' => $peers->where('status', 'connecting')->count(),
                'poor' => $peers->where('status', 'failed')->count(),
            ],
        ]);
    }
}

/**
 * BLADE TEMPLATE EXAMPLE (resources/views/tickets/stream.blade.php)
 */

?>
<!-- resources/views/tickets/stream.blade.php -->
@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 h-screen bg-slate-900">
    <!-- Main stream player (75% width) -->
    <div class="md:col-span-3">
        <div x-data="liveStream({{ $event->id }})" class="relative w-full h-full bg-black rounded-lg overflow-hidden">
            <!-- Local video (primary) -->
            <video 
                id="local-video" 
                autoplay 
                muted 
                playsinline 
                class="absolute inset-0 w-full h-full object-cover"
            ></video>
            
            <!-- Remote videos grid (overlay in corners) -->
            <div id="remote-videos" class="absolute bottom-4 right-4 grid gap-2" style="grid-template-columns: repeat(3, minmax(120px, 1fr));">
                <!-- Videos добавляются динамически -->
            </div>
            
            <!-- Pinned products overlay -->
            <div class="absolute top-4 left-4 bg-slate-900/80 backdrop-blur-sm rounded-lg p-4 max-w-xs">
                <h3 class="text-sm font-semibold text-white mb-3">{{ $event->title }}</h3>
                <div id="pinned-products" class="space-y-2">
                    {{-- Products injected via JavaScript --}}
                </div>
            </div>
            
            <!-- Stream controls -->
            <div class="absolute bottom-4 left-4 flex gap-2">
                <button 
                    @click="init()" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                >
                    Start Streaming
                </button>
                <button 
                    @click="destroy()" 
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
                >
                    Stop
                </button>
            </div>
            
            <!-- Peer count badge -->
            <div class="absolute top-4 right-4 bg-slate-900/90 backdrop-blur-sm rounded-lg px-3 py-2 text-sm text-white">
                👥 <span id="peer-count">0</span> viewers
            </div>
        </div>
    </div>
    
    <!-- Right sidebar: Chat & Info (25% width) -->
    <div class="md:col-span-1 bg-slate-800 rounded-lg p-4 overflow-y-auto">
        <!-- Event info -->
        <div class="space-y-4 mb-6">
            <div>
                <h2 class="text-lg font-bold text-white">{{ $event->title }}</h2>
                <p class="text-sm text-slate-400 mt-2">{{ $event->description }}</p>
            </div>
            
            <!-- Event metrics -->
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="bg-slate-700 rounded p-2">
                    <span class="text-slate-400">P2P</span>
                    <div id="topology-p2p" class="text-lg font-bold text-green-400">0</div>
                </div>
                <div class="bg-slate-700 rounded p-2">
                    <span class="text-slate-400">SFU</span>
                    <div id="topology-sfu" class="text-lg font-bold text-yellow-400">0</div>
                </div>
            </div>
        </div>
        
        <!-- Chat messages -->
        <div id="chat-messages" class="space-y-2 mb-4 h-64 overflow-y-auto">
            {{-- Chat messages --}}
        </div>
        
        <!-- Chat input -->
        <div class="flex gap-2">
            <input 
                type="text" 
                id="chat-input"
                placeholder="Message..." 
                class="flex-1 px-3 py-2 bg-slate-700 text-white rounded-lg text-sm placeholder-slate-500"
            />
            <button 
                class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition"
            >
                Send
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    import liveStream from '/resources/js/components/live-stream-player.js';
    
    // Register Alpine component
    Alpine.data('liveStream', liveStream);
    
    // Update peer count
    setInterval(async () => {
        const response = await fetch('/events/{{ $event->id }}/stream/metrics');
        const data = await response.json();
        document.getElementById('peer-count').textContent = data.connected_peers;
        document.getElementById('topology-p2p').textContent = 
            data.peer_details.filter(p => p.connection_type === 'p2p').length;
        document.getElementById('topology-sfu').textContent = 
            data.peer_details.filter(p => p.connection_type === 'sfu').length;
    }, 5000);
    
    // Load pinned products
    (async () => {
        const response = await fetch('/events/{{ $event->id }}/stream/products');
        const data = await response.json();
        const container = document.getElementById('pinned-products');
        
        data.products.forEach(product => {
            container.innerHTML += `
                <a href="${product.url}" class="block bg-slate-700/50 hover:bg-slate-700 rounded p-2 transition">
                    <img src="${product.image}" alt="${product.name}" class="w-full h-20 object-cover rounded mb-1">
                    <p class="text-xs text-white font-semibold truncate">${product.name}</p>
                    <p class="text-xs text-yellow-400 font-bold">₽${product.price}</p>
                </a>
            `;
        });
    })();
</script>
@endpush

@endsection
