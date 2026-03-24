/**
 * Live Stream Player Component (WebRTC Mesh P2P)
 * Alpine.js component for real-time streaming with Reverb + Laravel Echo
 * 
 * Usage:
 * <div x-data="liveStream({{ $stream->id }})" class="relative">
 *   <video id="local-video" autoplay muted playsinline></video>
 *   <div id="remote-videos"></div>
 * </div>
 */

export default function liveStream(streamId) {
    return {
        streamId,
        peerConnections: new Map(),
        localStream: null,
        localPeerId: null,
        roomId: null,
        turnServers: [],
        isInitialized: false,
        connectionStates: new Map(),

        /**
         * Initialize component
         */
        async init() {
            if (this.isInitialized) return;

            try {
                // Generate unique peer ID
                this.localPeerId = `peer_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
                this.roomId = `stream.${this.streamId}`;

                // Get local media stream
                this.localStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        facingMode: 'user',
                    },
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                    },
                });

                // Attach to video element
                const videoElement = document.getElementById('local-video');
                if (videoElement) {
                    videoElement.srcObject = this.localStream;
                }

                // Join stream via API
                const joinResponse = await this.apiCall('/mesh/join', {
                    peer_id: this.localPeerId,
                });

                this.turnServers = joinResponse.turn_servers || [];

                // Subscribe to Reverb channel
                this.subscribeToChannel();

                this.isInitialized = true;
                console.log('[WebRTC] Mesh initialized', {
                    peerId: this.localPeerId,
                    roomId: this.roomId,
                });
            } catch (error) {
                console.error('[WebRTC] Initialization failed:', error);
                alert('Failed to access camera/microphone: ' + error.message);
            }
        },

        /**
         * Subscribe to Reverb broadcast channel
         */
        subscribeToChannel() {
            if (!window.Echo) {
                console.error('[WebRTC] Laravel Echo not found');
                return;
            }

            // Join the stream room
            window.Echo.join(this.roomId)
                .here((users) => {
                    console.log('[WebRTC] Users in room:', users);
                })
                .joining((user) => {
                    console.log('[WebRTC] User joining:', user);
                })
                .leaving((user) => {
                    this.handlePeerLeft(user.id);
                })
                .listen('Stream.PeerJoined', (event) => {
                    this.handlePeerJoined(event);
                })
                .listen('Stream.OfferSent', (event) => {
                    this.handleOffer(event);
                })
                .listen('Stream.AnswerSent', (event) => {
                    this.handleAnswer(event);
                })
                .listen('Stream.IceCandidateSent', (event) => {
                    this.handleIceCandidate(event);
                });
        },

        /**
         * Handle new peer joining
         */
        async handlePeerJoined(event) {
            const { peer_id, peer_name } = event;
            console.log('[WebRTC] Peer joined:', peer_id, peer_name);

            if (peer_id === this.localPeerId) return;

            // Create connection with new peer
            const pc = this.createPeerConnection(peer_id, true);
            this.peerConnections.set(peer_id, pc);

            // Send offer to new peer
            try {
                const offer = await pc.createOffer({
                    offerToReceiveAudio: true,
                    offerToReceiveVideo: true,
                });

                await pc.setLocalDescription(offer);

                // Send offer via API
                await this.apiCall(`/mesh/offer`, {
                    from_peer: this.localPeerId,
                    to_peer: peer_id,
                    sdp: offer.sdp,
                });

                console.log('[WebRTC] Offer sent to', peer_id);
            } catch (error) {
                console.error('[WebRTC] Failed to send offer:', error);
            }
        },

        /**
         * Handle incoming offer
         */
        async handleOffer(event) {
            const { from, to, sdp } = event;

            if (to !== this.localPeerId) return;
            console.log('[WebRTC] Offer received from:', from);

            let pc = this.peerConnections.get(from);
            if (!pc) {
                pc = this.createPeerConnection(from, false);
                this.peerConnections.set(from, pc);
            }

            try {
                const remoteOffer = new RTCSessionDescription({
                    type: 'offer',
                    sdp,
                });

                await pc.setRemoteDescription(remoteOffer);

                // Create and send answer
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);

                await this.apiCall(`/mesh/answer`, {
                    from_peer: this.localPeerId,
                    to_peer: from,
                    sdp: answer.sdp,
                });

                console.log('[WebRTC] Answer sent to', from);
            } catch (error) {
                console.error('[WebRTC] Error handling offer:', error);
            }
        },

        /**
         * Handle incoming answer
         */
        async handleAnswer(event) {
            const { from, to, sdp } = event;

            if (to !== this.localPeerId) return;
            console.log('[WebRTC] Answer received from:', from);

            const pc = this.peerConnections.get(from);
            if (!pc) {
                console.warn('[WebRTC] No peer connection for', from);
                return;
            }

            try {
                const remoteAnswer = new RTCSessionDescription({
                    type: 'answer',
                    sdp,
                });

                await pc.setRemoteDescription(remoteAnswer);
            } catch (error) {
                console.error('[WebRTC] Error handling answer:', error);
            }
        },

        /**
         * Handle ICE candidate
         */
        async handleIceCandidate(event) {
            const { from, to, candidate, sdpMLineIndex, sdpMid } = event;

            if (to !== this.localPeerId) return;

            const pc = this.peerConnections.get(from);
            if (!pc) {
                console.warn('[WebRTC] No peer connection for ICE candidate from', from);
                return;
            }

            try {
                const iceCandidate = new RTCIceCandidate({
                    candidate,
                    sdpMLineIndex,
                    sdpMid,
                });

                await pc.addIceCandidate(iceCandidate);
            } catch (error) {
                console.error('[WebRTC] Error adding ICE candidate:', error);
            }
        },

        /**
         * Handle peer disconnection
         */
        handlePeerLeft(peerId) {
            console.log('[WebRTC] Peer left:', peerId);
            const pc = this.peerConnections.get(peerId);
            if (pc) {
                pc.close();
                this.peerConnections.delete(peerId);
            }

            // Remove remote video
            const videoElement = document.getElementById(`remote-${peerId}`);
            if (videoElement) {
                videoElement.remove();
            }
        },

        /**
         * Create RTCPeerConnection
         */
        createPeerConnection(peerId, isInitiator) {
            const config = {
                iceServers: this.turnServers || [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                ],
            };

            const pc = new RTCPeerConnection(config);

            // Add local tracks
            this.localStream.getTracks().forEach((track) => {
                pc.addTrack(track, this.localStream);
            });

            // Handle ICE candidates
            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    this.apiCall(`/mesh/ice-candidate`, {
                        peer_id: this.localPeerId,
                        candidate: event.candidate.candidate,
                        sdp_mline_index: event.candidate.sdpMLineIndex,
                        sdp_mid: event.candidate.sdpMid,
                    }).catch((error) => {
                        console.error('[WebRTC] Failed to send ICE candidate:', error);
                    });
                }
            };

            // Handle connection state changes
            pc.onconnectionstatechange = () => {
                console.log(`[WebRTC] Connection state with ${peerId}:`, pc.connectionState);
                this.connectionStates.set(peerId, pc.connectionState);

                if (pc.connectionState === 'connected') {
                    this.apiCall(`/mesh/connected`, {
                        peer_id: this.localPeerId,
                    }).catch((error) => {
                        console.error('[WebRTC] Failed to mark connected:', error);
                    });
                }

                if (
                    pc.connectionState === 'failed' ||
                    pc.connectionState === 'disconnected'
                ) {
                    this.apiCall(`/mesh/failed`, {
                        peer_id: this.localPeerId,
                        reason: pc.connectionState,
                    }).catch((error) => {
                        console.error('[WebRTC] Failed to mark failed:', error);
                    });
                }
            };

            // Handle remote stream
            pc.ontrack = (event) => {
                console.log('[WebRTC] Received remote track from', peerId, event.track.kind);
                this.attachRemoteStream(peerId, event.streams[0]);
            };

            return pc;
        },

        /**
         * Attach remote stream to video element
         */
        attachRemoteStream(peerId, stream) {
            let videoContainer = document.getElementById(`remote-${peerId}`);

            if (!videoContainer) {
                const remoteVideosDiv = document.getElementById('remote-videos');
                if (!remoteVideosDiv) return;

                videoContainer = document.createElement('video');
                videoContainer.id = `remote-${peerId}`;
                videoContainer.autoplay = true;
                videoContainer.playsinline = true;
                videoContainer.class = 'w-full h-full object-cover';
                remoteVideosDiv.appendChild(videoContainer);
            }

            videoContainer.srcObject = stream;
        },

        /**
         * Make API call to backend
         */
        async apiCall(endpoint, data = {}) {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error(`API call failed: ${response.statusText}`);
            }

            return response.json();
        },

        /**
         * Cleanup on destroy
         */
        destroy() {
            // Close all peer connections
            this.peerConnections.forEach((pc) => {
                pc.close();
            });
            this.peerConnections.clear();

            // Stop local stream
            if (this.localStream) {
                this.localStream.getTracks().forEach((track) => {
                    track.stop();
                });
            }

            // Leave channel
            if (window.Echo) {
                window.Echo.leave(this.roomId);
            }

            console.log('[WebRTC] Destroyed');
        },
    };
}
