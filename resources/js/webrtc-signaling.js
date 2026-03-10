export default function webRTC(roomId, turnConfig) {
    return {
        pc: null, localStream: null, remoteStream: null,
        async init() {
            this.localStream = await navigator.mediaDevices.getUserMedia({video: true, audio: true});
            this.$refs.localVideo.srcObject = this.localStream;
            this.pc = new RTCPeerConnection(turnConfig);
            this.localStream.getTracks().forEach(t => this.pc.addTrack(t, this.localStream));
            this.pc.ontrack = e => {
                this.remoteStream = e.streams[0];
                this.$refs.remoteVideo.srcObject = this.remoteStream;
            };
            this.setupSignaling(roomId);
        },
        setupSignaling(roomId) {
            Echo.join(`VideoCall.${roomId}`)
                .here(users => this.handleHere(users))
                .joining(user => this.handleJoining(user))
                .listen('WebRTC.Offer', e => this.handleOffer(e))
                .listen('WebRTC.Answer', e => this.handleAnswer(e))
                .listen('WebRTC.Candidate', e => this.pc.addIceCandidate(e.candidate));
        },
        async handleJoining(user) {
            const offer = await this.pc.createOffer();
            await this.pc.setLocalDescription(offer);
            axios.post(`/api/webrtc/offer/${roomId}`, { offer });
        }
    }
}
