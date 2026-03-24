# ⚡ QUICK START — WebRTC Live Streaming Integration

**Time to Live:** 10 minutes  
**Difficulty:** Easy  

---

## 1️⃣ Install (2 minutes)

```bash
cd /path/to/catvrf

# Install Reverb
composer require laravel/reverb

# Run setup
php artisan reverb:install

# Run migrations
php artisan migrate

# Or for multi-tenant:
php artisan tenants:migrate
```

---

## 2️⃣ Configure (2 minutes)

### Copy `.env` variables

```bash
# Add to .env
cp .env.webrtc.example /tmp/webrtc.env

# Manually copy these lines to .env:
BROADCAST_DRIVER=reverb
REVERB_HOST=0.0.0.0
REVERB_PORT=6001
REVERB_SCHEME=http
REVERB_APP_ID=laravel
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret

# TURN (critical!)
WEBRTC_STUN=stun:stun.l.google.com:19302
WEBRTC_TURN_URL=turn:your-turn-server:3478
WEBRTC_TURN_USERNAME=username
WEBRTC_TURN_CREDENTIAL=credential
```

### Update `resources/js/bootstrap.js`

```javascript
// Add or update Echo configuration
import Echo from 'laravel-echo';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### Update `.env.js` (Vite)

```javascript
VITE_REVERB_APP_KEY=your-app-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=6001
VITE_REVERB_SCHEME=http
```

---

## 3️⃣ Start Servers (2 minutes)

### Terminal 1: Reverb WebSocket

```bash
php artisan reverb:start --host=0.0.0.0 --port=6001

# Output should show:
# Starting Reverb server
# Listening on ws://0.0.0.0:6001
```

### Terminal 2: Laravel

```bash
php artisan serve

# Or with npm dev:
npm run dev
```

---

## 4️⃣ Create Event & Blade (2 minutes)

### Create Blade template (resources/views/stream.blade.php)

```blade
@extends('layouts.app')

@section('content')
<div x-data="liveStream({{ $stream->id }})" class="w-full h-screen bg-black">
    <video id="local-video" autoplay muted class="w-full h-full"></video>
    <div id="remote-videos" class="absolute bottom-4 right-4 grid gap-2"></div>
    
    <button @click="init()" class="absolute bottom-4 left-4 px-4 py-2 bg-blue-600 text-white rounded">
        Start
    </button>
</div>

@vite(['resources/js/components/live-stream-player.js'])
<script type="module">
    import liveStream from '/resources/js/components/live-stream-player.js';
    Alpine.data('liveStream', liveStream);
</script>
@endsection
```

### Add route (routes/web.php)

```php
Route::get('/stream/{stream}', function ($stream) {
    return view('stream', ['stream' => Event::find($stream)]);
});
```

---

## 5️⃣ Test (2 minutes)

### Open in 2 browsers

```
Browser A: http://localhost:8000/stream/1
Browser B: http://localhost:8000/stream/1
```

### Click "Start" in both

You should see each other's video! 🎉

---

## ✅ Verification Checklist

```bash
# 1. Check Reverb is running
curl http://localhost:6001
# Should not error

# 2. Check database
php artisan tinker
> StreamPeerConnection::latest()->first()
# Should show peer connections

# 3. Check logs
tail -f storage/logs/laravel.log | grep -i mesh
# Should show peer joined events

# 4. Check broadcast
php artisan tinker
> broadcast(new \App\Events\Stream\PeerJoined(1, 'peer_123'))
# Should broadcast successfully
```

---

## 🐛 If it doesn't work

### Problem: "No cameras/microphone access"

```javascript
// Check browser console (F12)
navigator.mediaDevices.getUserMedia({video: true, audio: true})
    .then(stream => console.log('✅ OK'))
    .catch(err => console.error('❌ Error:', err));
```

**Solution:** Allow camera access when browser asks.

### Problem: "Reverb connection failed"

```bash
# Check Reverb is really running
ps aux | grep reverb

# Check firewall
telnet localhost 6001

# Restart Reverb
php artisan reverb:start
```

### Problem: "ICE gathering timeout"

```bash
# Your TURN server is probably not configured
# Check in .env:
echo $WEBRTC_TURN_URL

# If empty, P2P only works in same LAN
```

---

## 📚 Next Steps

1. **Read:** [WEBRTC_LIVE_STREAMING_GUIDE.md](WEBRTC_LIVE_STREAMING_GUIDE.md)
2. **Implement:** [WEBRTC_USAGE_EXAMPLE.php](WEBRTC_USAGE_EXAMPLE.php)
3. **Monitor:** [WEBRTC_SQL_QUERIES.sql](WEBRTC_SQL_QUERIES.sql)
4. **Troubleshoot:** [WEBRTC_TROUBLESHOOTING_FAQ.md](WEBRTC_TROUBLESHOOTING_FAQ.md)

---

## 🎯 Integration with Tickets Vertical

```php
// In app/Filament/Tenant/Resources/EventResource.php

public function table(Table $table): Table
{
    return $table
        ->columns([
            // ... other columns
            BadgeColumn::make('is_live')
                ->label('Live')
                ->formatStateUsing(fn ($state) => $state ? '🔴 Live' : '⚪ Offline'),
        ])
        ->actions([
            Action::make('stream')
                ->label('Watch Stream')
                ->url(fn ($record) => route('stream.show', $record))
                ->openUrlInNewTab(),
        ]);
}
```

---

## 🚀 Production Checklist

- [ ] TURN server configured and tested
- [ ] Reverb running on dedicated port
- [ ] Firewall rules for port 6001 & 3478
- [ ] SSL/TLS certificates (for HTTPS)
- [ ] Database backed up
- [ ] Monitoring alerts set up
- [ ] Cleanup job scheduled (see MeshService)

---

## 📞 Questions?

1. Check logs: `storage/logs/laravel.log`
2. Read docs: `WEBRTC_*.md` files
3. Test with SQL: `WEBRTC_SQL_QUERIES.sql`
4. See examples: `WEBRTC_USAGE_EXAMPLE.php`

---

**You're ready to go! 🚀**

Real-time P2P video streaming is now live in your platform.
