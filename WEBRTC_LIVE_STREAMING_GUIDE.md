# WebRTC P2P Mesh Live Streaming (Reverb + Laravel)

Это полная реализация **WebRTC peer-to-peer mesh-сетки** для live-streaming на базе Laravel Reverb и прямого SDP обмена между пользователями.

## 📋 Оглавление

1. [Структура файлов](#структура-файлов)
2. [Установка (10 минут)](#установка-10-минут)
3. [Конфигурация](#конфигурация)
4. [Как работает](#как-работает)
5. [Безопасность & Multi-tenant](#безопасность--multi-tenant)
6. [Auto-switch P2P → SFU](#auto-switch-p2p--sfu)
7. [Тестирование](#тестирование)

---

## 📁 Структура файлов

```
app/
├── Services/
│   └── MeshService.php                    # Ядро: управление P2P соединениями
├── Models/
│   └── StreamPeerConnection.php           # Модель для хранения соединений
├── Events/Stream/
│   ├── OfferSent.php                      # Broadcast event: SDP offer
│   ├── AnswerSent.php                     # Broadcast event: SDP answer
│   ├── IceCandidateSent.php               # Broadcast event: ICE candidate
│   └── PeerJoined.php                     # Broadcast event: peer joined
├── Http/Controllers/
│   └── MeshController.php                 # API endpoints для SDP обмена
├── Jobs/
│   └── CleanupStreamPeerConnectionsJob.php # Cleanup старых соединений
│
database/
├── migrations/
│   └── 2026_03_23_120000_create_stream_peer_connections_table.php
├── factories/
│   └── StreamPeerConnectionFactory.php
│
resources/js/
├── components/
│   └── live-stream-player.js              # Alpine.js frontend компонент
│
routes/
├── channels.php                           # Broadcast channels (multi-tenant)
├── web.php                                # API routes
│
config/
└── broadcasting.php                       # Reverb + WebRTC конфигурация
```

---

## 🚀 Установка (10 минут)

### 1. Установка зависимостей

```bash
# Laravel Reverb (native WebSocket для Laravel)
composer require laravel/reverb

# Генерируем конфиг
php artisan reverb:install

# Запускаем Reverb сервер (можно в отдельном терминале)
php artisan reverb:start --host=0.0.0.0 --port=6001
```

### 2. Добавьте в `.env`

```env
# Broadcasting
BROADCAST_DRIVER=reverb

# Reverb
REVERB_HOST=0.0.0.0
REVERB_PORT=6001
REVERB_SCHEME=http
REVERB_APP_ID=laravel
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret

# WebRTC STUN/TURN
WEBRTC_STUN=stun:stun.l.google.com:19302

# TURN сервер (критично для production!)
WEBRTC_TURN_URL=turn:your-turn-server:3478
WEBRTC_TURN_USERNAME=username
WEBRTC_TURN_CREDENTIAL=credential
```

### 3. Запустите миграции

```bash
php artisan migrate

# Или если в тенанте:
php artisan tenants:migrate
```

### 4. Обновите Bootstrap JavaScript (resources/js/bootstrap.js)

Убедитесь, что у вас есть Laravel Echo подключение:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Для Reverb используем WebSocket
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

### 5. Обновите `.env.js` (для Vite)

```env
VITE_REVERB_APP_KEY=your-app-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=6001
VITE_REVERB_SCHEME=http
```

### 6. В Blade шаблоне

```blade
<div x-data="liveStream({{ $stream->id }})" @destroy="destroy()">
    <!-- Local video -->
    <div class="relative w-full">
        <video id="local-video" autoplay muted playsinline class="w-full h-auto bg-black"></video>
    </div>
    
    <!-- Remote videos grid -->
    <div id="remote-videos" class="grid grid-cols-2 gap-2 md:grid-cols-3">
        <!-- Videos добавляются динамически -->
    </div>
    
    <button @click="init()" class="px-4 py-2 bg-blue-600 text-white rounded">
        Start Streaming
    </button>
</div>

@vite(['resources/js/components/live-stream-player.js'])
<script>
    import liveStream from '/resources/js/components/live-stream-player.js';
    Alpine.data('liveStream', liveStream);
</script>
```

---

## 🔧 Конфигурация

### Reverb конфигурация (config/reverb.php)

```php
// После php artisan reverb:install
return [
    'apps' => [
        [
            'id' => env('REVERB_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'max_connections' => 10000, // Максимум одновременных соединений
            'max_backoff' => 120,
            'secure_headers' => [],
        ],
    ],
    'host' => env('REVERB_HOST', '0.0.0.0'),
    'port' => env('REVERB_PORT', 6001),
    'options' => [
        'profile' => env('LOG_CHANNEL') === 'local' ? 'debug' : 'production',
    ],
];
```

### Broadcasting конфигурация (config/broadcasting.php)

```php
'default' => env('BROADCAST_DRIVER', 'reverb'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'host' => env('REVERB_HOST', '0.0.0.0'),
        'port' => env('REVERB_PORT', 6001),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'app_id' => env('REVERB_APP_ID', 'laravel'),
        'app_key' => env('REVERB_APP_KEY', 'your-app-key'),
        'app_secret' => env('REVERB_APP_SECRET', 'your-app-secret'),
    ],
],

'webrtc' => [
    'stun' => env('WEBRTC_STUN', 'stun:stun.l.google.com:19302'),
    'turn' => [
        'url' => env('WEBRTC_TURN_URL'),
        'username' => env('WEBRTC_TURN_USERNAME'),
        'credential' => env('WEBRTC_TURN_CREDENTIAL'),
    ],
],
```

---

## 🎯 Как работает

### 1️⃣ Peer присоединяется к стриму

```php
// API: POST /mesh/join
// Frontend вызывает это при инициализации
$mesh->joinRoom($stream, $user, $peerId);

// Backend:
// - Создаёт StreamPeerConnection запись в БД
// - Broadcasts "PeerJoined" событие всем в канале
// - Все остальные пиры видят новый peer_id и создают RTCPeerConnection
```

### 2️⃣ Offer/Answer обмен (SDP)

```
Peer A (инициатор)           Peer B (получатель)
        |                              |
        +------------ offer --------->+  (API: POST /mesh/offer)
        |                              |
        +<----------- answer ----------+  (API: POST /mesh/answer)
        |                              |
        +<----- ice-candidate -------->+  (API: POST /mesh/ice-candidate)
        |                              |
        +========= WebRTC P2P ========>+  (прямое соединение)
```

### 3️⃣ ICE Candidates (NAT Traversal)

- Каждый peer генерирует ICE candidates
- Отправляет через `POST /mesh/ice-candidate`
- Backend broadcastит другому пиру
- Оба пира пытаются подключиться через возможные пути (direct, STUN, TURN)

### 4️⃣ Peer Connection Established

```
frontend:
  pc.onconnectionstatechange = () => {
    if (pc.connectionState === 'connected') {
      // Отправляем в backend
      POST /mesh/connected { peer_id }
      
      // Backend проверяет: может ли нужен auto-switch на SFU?
    }
  }
```

---

## 🔐 Безопасность & Multi-tenant

### Tenant Scoping (обязателен!)

```php
// routes/channels.php
Broadcast::channel('stream.{streamId}', function ($user, $streamId) {
    $stream = Event::find($streamId);
    
    // Критично: проверяем tenant_id
    if ($user->tenant_id !== $stream->tenant_id) {
        return false;  // Запрещаем доступ
    }
    
    return ['id' => $user->id, 'name' => $user->name];
});
```

### Fraud Control

```php
// MeshService::joinRoom()
$this->fraudControl->check([
    'type' => 'stream_join',
    'user_id' => $user->id,
    'stream_id' => $stream->id,
], $correlationId);
```

### Audit Logging

Все события логируются в `audit` канал:

```php
Log::channel('audit')->info('Peer joined stream', [
    'peer_id' => $peerId,
    'stream_id' => $stream->id,
    'user_id' => $user->id,
    'tenant_id' => $stream->tenant_id,
    'correlation_id' => $correlationId,
]);
```

### Rate Limiting

```php
// В контроллере можно добавить
Route::middleware(['rate-limit:50,1'])->group(function () {
    Route::post('/mesh/join', ...);
    Route::post('/mesh/offer', ...);
});
```

---

## 🔄 Auto-switch P2P → SFU

Когда количество пиров > 15, система переключается на **SFU** (Selective Forwarding Unit) топологию:

```php
// MeshService::checkTopology()
public function checkTopology(Event $stream, int $threshold = 15): bool
{
    $connectedCount = StreamPeerConnection::forStream($stream->id)->connected()->count();
    
    if ($connectedCount > $threshold) {
        // Switch to SFU
        $stream->update(['topology' => 'sfu']);
        StreamPeerConnection::forStream($stream->id)
            ->update(['connection_type' => 'sfu']);
        
        return true;
    }
    
    return false;
}
```

### Рекомендуемые SFU решения:

- **MiroTalk** — самый простой (Docker)
- **Mediasoup** — мощный, гибкий (Node.js)
- **Janus** — универсальный (C)

---

## 🧪 Тестирование

### Локальный тест (2 браузера одновременно)

```bash
# Terminal 1: Запустить Reverb
php artisan reverb:start

# Terminal 2: Запустить Laravel
php artisan serve

# Открыть в 2 браузерах
# http://localhost:8000/streams/1 (Peer A)
# http://localhost:8000/streams/1 (Peer B, в другом браузере)

# Нажимаем "Start Streaming" в обоих
# Должны увидеть видео друг друга
```

### Мониторинг Reverb

```bash
# Смотреть логи WebSocket соединений
tail -f storage/logs/reverb.log
```

### API тестирование (Postman/curl)

```bash
# 1. Join
POST http://localhost:8000/mesh/join
{
  "peer_id": "peer_123456"
}

# Ответ:
{
  "status": "joined",
  "peer_id": "peer_123456",
  "room_id": "stream.1",
  "turn_servers": [...]
}

# 2. Send Offer
POST http://localhost:8000/mesh/offer
{
  "from_peer": "peer_123456",
  "to_peer": "peer_789012",
  "sdp": "v=0\r\no=- ..."
}

# 3. Add ICE Candidate
POST http://localhost:8000/mesh/ice-candidate
{
  "peer_id": "peer_123456",
  "candidate": "candidate:...",
  "sdp_mline_index": 0,
  "sdp_mid": "0"
}
```

---

## 📊 Мониторинг & Аналитика

### Дашборд для админа

```php
// Filament Resource: StreamPeerConnectionResource
public function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            InfolistSection::make('Peer Info')
                ->schema([
                    TextEntry::make('peer_id'),
                    TextEntry::make('stream.title'),
                    TextEntry::make('user.name'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'connected' => 'success',
                            'failed' => 'danger',
                            default => 'gray'
                        }),
                    TextEntry::make('connection_type')->badge(),
                    TextEntry::make('ice_candidates')->listWithLineBreaks(),
                ]),
        ]);
}
```

### Метрики

- Количество активных пиров
- Успешных соединений / неудач
- Среднее время подключения
- Topology switches (P2P → SFU)

---

## ⚠️ Важные заметки

### TURN сервер критичен!

**Без TURN сервера:**
- 30% пользователей подключатся (только direct + STUN)
- 70% не смогут пройти через NAT

**С TURN сервером:**
- 99%+ успешных соединений
- Небольшая задержка (реле трафика)

### Рекомендуемые TURN провайдеры:

| Провайдер | Цена | Масштаб | Примечание |
|-----------|------|---------|-----------|
| **coturn (self-hosted)** | $5-20/мес (VPS) | до 10k concurrent | Лучший для production |
| **Twilio** | $0.015/GB | Unlimited | Дорого при большом трафике |
| **xirsys** | $0-99/мес | до 1M users | Хороший баланс |
| **Google STUN** | Бесплатно | Limited | Только STUN (не TURN) |

---

## 📚 Дополнительные ресурсы

- [WebRTC.org](https://webrtc.org/)
- [IETF RFC 7675 (ICE candidates)](https://tools.ietf.org/html/rfc7675)
- [MDN WebRTC API](https://developer.mozilla.org/en-US/docs/Web/API/WebRTC_API)
- [Laravel Reverb Docs](https://laravel.com/docs/reverb)
- [coturn GitHub](https://github.com/coturn/coturn)

---

## 🎓 Что дальше?

1. **Data Channels** — отправлять дополнительные данные через P2P (pinned товары, NFT-подарки)
2. **Recording** — записывать стримы на сервер
3. **Screen Sharing** — добавить демонстрацию экрана
4. **Advanced Analytics** — отслеживать bandwidth, latency, packet loss
5. **Simulcast** — отправлять несколько качеств видео одновременно

---

**Версия:** March 2026  
**Статус:** Production-Ready ✅  
**Поддержка:** Laravel 11+, PHP 8.3+
