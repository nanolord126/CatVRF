<!-- Live Stream Player with Overlay -->
<div class="live-stream-container" 
     x-data="streamPlayer({{ $stream->toJson() }})"
     @streamStarted.window="onStreamStarted($event)"
     @streamEnded.window="onStreamEnded($event)"
     class="relative w-full h-screen bg-black">

    <!-- Video Canvas / WebRTC -->
    <div class="relative w-full h-full">
        <video 
            id="remote-video" 
            class="w-full h-full object-contain"
            playsinline
            autoplay
        ></video>

        <!-- Loading Indicator -->
        <template x-if="isLoading">
            <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                <div class="animate-spin">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
            </div>
        </template>

        <!-- Pinned Products Overlay (Right Panel) -->
        <div class="absolute right-0 top-0 h-full w-80 bg-gradient-to-l from-black to-transparent p-4 overflow-y-auto">
            <h3 class="text-white font-bold mb-4 text-lg">Featured Products</h3>

            <template x-if="pinnedProducts.length > 0">
                <div class="space-y-4">
                    <template x-for="product in pinnedProducts" :key="product.id">
                        <div class="bg-gray-900 rounded-lg overflow-hidden hover:bg-gray-800 transition cursor-pointer"
                             @click="selectProduct(product)">
                            <img :src="product.product_image_url" class="w-full h-32 object-cover">
                            <div class="p-3">
                                <p class="text-white font-semibold text-sm truncate" x-text="product.product_name"></p>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="text-red-500 font-bold" x-text="`₽${(product.price_during_stream).toFixed(2)}`"></div>
                                    <template x-if="product.original_price > product.price_during_stream">
                                        <span class="text-xs text-gray-400 line-through" 
                                              x-text="`₽${(product.original_price).toFixed(2)}`"></span>
                                    </template>
                                </div>
                                <button @click.stop="addToCart(product)"
                                        class="w-full mt-2 bg-blue-600 hover:bg-blue-700 text-white text-xs py-2 rounded transition">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Chat + Gifts (Left Panel) -->
        <div class="absolute left-0 top-0 h-full w-80 bg-gradient-to-r from-black to-transparent p-4 flex flex-col">
            <!-- Chat Messages -->
            <div class="flex-1 overflow-y-auto mb-4 space-y-2" id="chat-messages">
                <template x-for="message in messages" :key="message.id">
                    <div class="text-white text-sm">
                        <span class="font-semibold text-blue-400" x-text="message.user_name"></span>
                        <span x-text="message.message"></span>
                    </div>
                </template>
            </div>

            <!-- Chat Input -->
            <div class="flex gap-2">
                <input 
                    type="text" 
                    placeholder="Type message..."
                    class="flex-1 bg-gray-900 text-white rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @keydown.enter="sendMessage"
                    x-model="newMessage"
                >
                <button @click="sendMessage"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded transition text-sm">
                    Send
                </button>
            </div>

            <!-- Gift Button -->
            <button @click="showGiftModal = true"
                    class="w-full mt-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-2 rounded transition font-semibold">
                💎 Send Gift
            </button>
        </div>

        <!-- Live Badge + Viewer Count (Top) -->
        <div class="absolute top-4 left-4 flex items-center gap-4">
            <div class="bg-red-600 text-white px-3 py-1 rounded-full flex items-center gap-2 animate-pulse">
                <span class="w-2 h-2 bg-white rounded-full"></span>
                LIVE
            </div>
            <div class="text-white font-semibold flex items-center gap-2">
                👥 <span x-text="viewerCount.toLocaleString()"></span>
            </div>
        </div>
    </div>

    <!-- Gift Modal -->
    <template x-if="showGiftModal">
        <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50"
             @click="showGiftModal = false">
            <div class="bg-gray-900 rounded-lg p-6 w-96" @click.stop>
                <h2 class="text-white text-2xl font-bold mb-4">Send a Gift 🎁</h2>

                <div class="grid grid-cols-4 gap-3 mb-4">
                    <template x-for="gift in availableGifts" :key="gift.id">
                        <button @click="selectedGift = gift"
                                :class="selectedGift?.id === gift.id ? 'ring-2 ring-purple-500' : ''"
                                class="aspect-square bg-gray-800 rounded-lg p-2 hover:bg-gray-700 transition">
                            <img :src="gift.gift_image_url" class="w-full h-full object-contain">
                        </button>
                    </template>
                </div>

                <template x-if="selectedGift">
                    <div>
                        <p class="text-white mb-4">
                            Price: <span class="text-lg font-bold text-red-500" 
                                    x-text="`₽${(selectedGift.gift_price).toFixed(2)}`"></span>
                        </p>
                        <button @click="sendGift()"
                                :disabled="isSendingGift"
                                class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-bold hover:from-purple-700 hover:to-pink-700 transition disabled:opacity-50">
                            <span x-show="!isSendingGift">Send Gift</span>
                            <span x-show="isSendingGift">Minting on TON...</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- Shopping Cart Sidebar -->
    <template x-if="showCart">
        <div class="absolute bottom-0 right-0 w-80 bg-gray-900 rounded-t-lg p-4 border-t border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white font-bold text-lg">Shopping Cart</h3>
                <button @click="showCart = false" class="text-gray-400 hover:text-white">✕</button>
            </div>

            <div class="space-y-3 max-h-48 overflow-y-auto mb-4">
                <template x-for="item in cartItems" :key="item.product_id">
                    <div class="flex justify-between items-center text-white text-sm">
                        <span x-text="item.product_name"></span>
                        <span x-text="`₽${(item.total).toFixed(2)}`"></span>
                    </div>
                </template>
            </div>

            <div class="border-t border-gray-700 pt-3 mb-4">
                <div class="flex justify-between text-white font-bold">
                    <span>Total:</span>
                    <span x-text="`₽${(cartTotal).toFixed(2)}`"></span>
                </div>
            </div>

            <button @click="checkout()"
                    class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-bold transition">
                Checkout (SBP/YuKassa)
            </button>
        </div>
    </template>
</div>

<script>
function streamPlayer(stream) {
    return {
        stream: stream,
        isLoading: true,
        viewerCount: 0,
        pinnedProducts: [],
        messages: [],
        newMessage: '',
        showGiftModal: false,
        showCart: false,
        availableGifts: [],
        selectedGift: null,
        cartItems: [],
        cartTotal: 0,
        isSendingGift: false,

        init() {
            this.setupWebRTC();
            this.setupReverb();
            this.loadPinnedProducts();
            this.loadAvailableGifts();
        },

        setupWebRTC() {
            // const peer = new SimplePeer({ initiator: false, streams: [localStream] });
            this.isLoading = false;
        },

        setupReverb() {
            // Subscribe to Reverb channel
            Echo.channel(`stream.${this.stream.room_id}`)
                .listen('ChatMessageSent', (e) => {
                    this.messages.push({
                        id: e.id,
                        user_name: e.user_name,
                        message: e.message,
                    });
                    this.$nextTick(() => {
                        const chatDiv = document.getElementById('chat-messages');
                        chatDiv.scrollTop = chatDiv.scrollHeight;
                    });
                })
                .listen('ProductPinned', (e) => {
                    this.loadPinnedProducts();
                })
                .listen('GiftSent', (e) => {
                    this.showGiftAnimation(e);
                });
        },

        loadPinnedProducts() {
            fetch(`/api/streams/${this.stream.id}/products?pinned=true`)
                .then(r => r.json())
                .then(data => this.pinnedProducts = data);
        },

        loadAvailableGifts() {
            fetch('/api/gifts/available')
                .then(r => r.json())
                .then(data => this.availableGifts = data);
        },

        sendMessage() {
            if (!this.newMessage.trim()) return;

            fetch(`/api/streams/${this.stream.id}/chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ message: this.newMessage }),
            });

            this.newMessage = '';
        },

        selectProduct(product) {
            this.selectedProduct = product;
        },

        addToCart(product) {
            const existing = this.cartItems.find(i => i.product_id === product.id);
            if (existing) {
                existing.quantity++;
                existing.total = existing.quantity * product.price_during_stream;
            } else {
                this.cartItems.push({
                    product_id: product.id,
                    product_name: product.product_name,
                    quantity: 1,
                    price: product.price_during_stream,
                    total: product.price_during_stream,
                });
            }
            this.updateCartTotal();
            this.showCart = true;
        },

        updateCartTotal() {
            this.cartTotal = this.cartItems.reduce((sum, i) => sum + i.total, 0);
        },

        async checkout() {
            const response = await fetch(`/api/streams/${this.stream.id}/checkout`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ items: this.cartItems }),
            });

            const { payment_url } = await response.json();
            window.location.href = payment_url;
        },

        async sendGift() {
            if (!this.selectedGift) return;

            this.isSendingGift = true;

            try {
                const response = await fetch(`/api/streams/${this.stream.id}/gifts`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({
                        gift_id: this.selectedGift.id,
                        gift_type: this.selectedGift.type,
                        gift_price: this.selectedGift.price,
                    }),
                });

                const gift = await response.json();
                this.showGiftModal = false;
                this.showGiftAnimation(gift);
            } finally {
                this.isSendingGift = false;
            }
        },

        showGiftAnimation(gift) {
            // Floating animation for gift received
            console.log('Gift received:', gift);
        },

        onStreamStarted(event) {
            this.isLoading = false;
        },

        onStreamEnded(event) {
            // Redirect to VOD or thank you page
        },
    };
}
</script>
