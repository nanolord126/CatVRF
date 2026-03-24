# WebRTC Live Streaming - Troubleshooting & FAQ

## 🔴 Проблемы и решения

### ❌ Проблема: "WebRTC соединение не устанавливается"

**Симптомы:**
- Видео не загружается
- Status остаётся в "connecting"
- Нет ошибок в консоли браузера

**Решения (в порядке приоритета):**

#### 1️⃣ Проверьте TURN сервер

```bash
# Если TURN сервер не настроен, P2P соединение может не пройти через NAT
# 70% пользователей нуждаются в TURN!

# Проверьте в .env:
WEBRTC_TURN_URL=turn:your-turn-server:3478
WEBRTC_TURN_USERNAME=username
WEBRTC_TURN_CREDENTIAL=credential
```

**Если используете coturn:**
```bash
# Проверьте, что сервер запущен
systemctl status coturn

# Проверьте логи
tail -f /var/log/coturn/turnserver.log

# Тестируйте подключение
turnutils_uclient -v -u username -w credential -p 3478 your-public-ip
```

#### 2️⃣ Проверьте Reverb

```bash
# Убедитесь, что Reverb сервер запущен
php artisan reverb:start --host=0.0.0.0 --port=6001

# В логах должно быть:
# Starting Reverb server
# Listening on ws://0.0.0.0:6001

# Проверьте .env
BROADCAST_DRIVER=reverb
REVERB_HOST=0.0.0.0
REVERB_PORT=6001
```

#### 3️⃣ Проверьте firewall

```bash
# Если за NAT/firewall, разрешите:
# - Port 6001 (Reverb WebSocket)
# - Port 3478 UDP/TCP (TURN сервер)
# - Диапазон 49152-65535 UDP (RTP медиа)

# Тестируйте с curl:
curl -v http://localhost:6001
```

#### 4️⃣ Проверьте браузер консоль

```javascript
// В консоли браузера (F12 → Console):

// 1. Проверьте, загружен ли Echo
console.log(window.Echo);  // Должен быть объект

// 2. Проверьте подключение к каналу
window.Echo.join('stream.1')
  .subscribed(() => console.log('Subscribed!'))
  .error(error => console.error('Error:', error));

// 3. Проверьте WebSocket
console.log(window.Echo.connector.socket.readyState);
// 0 = CONNECTING
// 1 = OPEN (✅ хорошо)
// 2 = CLOSING
// 3 = CLOSED (❌ проблема)
```

#### 5️⃣ Проверьте SDP

```javascript
// SDP должен быть валидным:
const sdp = "v=0\r\no=- 1234567890 2 IN IP4 127.0.0.1\r\n...";

// Проверьте:
console.log(sdp.includes('v=0'));     // ✅ должно быть true
console.log(sdp.includes('m=audio') || sdp.includes('m=video')); // ✅
console.log(sdp.includes('a='));      // ✅ атрибуты
```

---

### ❌ Проблема: "Reverb соединение падает"

**Логи:**
```
Error: WebSocket connection closed
Connection lost, attempting to reconnect...
```

**Решения:**

#### Причина 1: Reverb перезагрузился

```bash
# Перезагрузите Reverb
php artisan reverb:start --host=0.0.0.0 --port=6001

# Или используйте Supervisor для автозагрузки
cat > /etc/supervisor/conf.d/reverb.conf << 'EOF'
[program:reverb]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan reverb:start --host=0.0.0.0 --port=6001
autostart=true
autorestart=true
numprocs=1
user=www-data
EOF

supervisorctl update
```

#### Причина 2: Memory leak

```bash
# Reverb требует достаточно памяти
# На 10,000 соединений требуется ~2GB RAM

# Проверьте использование памяти
ps aux | grep reverb

# Ограничьте PHP-CLI память
php -d memory_limit=4G artisan reverb:start
```

#### Причина 3: File descriptors

```bash
# Увеличьте лимит open files
ulimit -n 65536

# Постоянно в /etc/security/limits.conf
www-data soft nofile 65536
www-data hard nofile 65536
```

---

### ❌ Проблема: "ICE candidates не добавляются"

**Симптомы:**
- SDP обменялся, но видео всё равно не подключается
- `ice_candidates` остаётся пустым в БД

**Решения:**

```javascript
// Проверьте, что RTCPeerConnection генерирует candidates:
const pc = new RTCPeerConnection(config);

pc.onicecandidate = (event) => {
    if (event.candidate) {
        console.log('ICE candidate:', event.candidate);
        
        // Отправьте на сервер
        fetch('/mesh/ice-candidate', {
            method: 'POST',
            body: JSON.stringify({
                peer_id: myPeerId,
                candidate: event.candidate.candidate,
                sdp_mline_index: event.candidate.sdpMLineIndex,
                sdp_mid: event.candidate.sdpMid
            })
        });
    } else {
        console.log('ICE gathering complete'); // ✅ нормально
    }
};

// Добавьте локальные IP в SDP (для LAN)
const sdp = localDescription.sdp;
console.log(sdp); // должны видеть IP адреса
```

---

### ❌ Проблема: "Auto-switch на SFU не происходит"

**Проверка:**

```php
// В MeshController::connected()
$switched = $this->mesh->checkTopology($stream);

if ($switched) {
    // SFU switched!
    Log::info('Topology switched to SFU');
}

// Проверьте БД
SELECT * FROM events WHERE id = 1;
// `topology` должно быть 'sfu'
```

**Если не переключается:**

```php
// Проверьте порог (по умолчанию 15)
$switched = $this->mesh->checkTopology($stream, 15);

// Проверьте, что пиры действительно connected
SELECT COUNT(*) FROM stream_peer_connections
WHERE stream_id = 1 AND status = 'connected';
```

---

### ❌ Проблема: "Rate limiting блокирует запросы"

**Ошибка:**
```
HTTP 429 Too Many Requests
Retry-After: 60
```

**Решения:**

```php
// Увеличьте лимиты в routes/web.php
Route::middleware(['rate-limit:100,1'])->group(function () {  // Было 50
    Route::post('/mesh/ice-candidate', ...);
});

// Или используйте tenant-aware rate limiting
Route::middleware(['rate-limit-tenant:100,1'])->group(function () {
    Route::post('/mesh/offer', ...);
});
```

---

## ❓ FAQ (Часто задаваемые вопросы)

### Q: Может ли P2P сетка масштабироваться на 1000+ пиров?

**A:** Нет. P2P mesh имеет O(n²) сложность:
- 10 пиров = 45 соединений
- 100 пиров = 4,950 соединений
- 1000 пиров = 499,500 соединений ❌

**Рекомендация:** Auto-switch на SFU при >15 пиров. С SFU:
- 1000 пиров = 1000 соединений (линейно)

---

### Q: Какой bandwidth требуется для P2P стрима?

**A:**

| Качество | Одна ссылка | 10 пиров (upload) | 100 пиров (upload) |
|----------|-----------|---|---|
| **480p, 30fps** | 1.5 Mbps | 15 Mbps | 150 Mbps ❌ |
| **720p, 30fps** | 2.5 Mbps | 25 Mbps | 250 Mbps ❌ |
| **1080p, 60fps** | 8 Mbps | 80 Mbps | 800 Mbps ❌ |

**Вывод:** P2P работает только для небольших групп (<15 пиров).

---

### Q: Может ли пользователь видеть других пиров?

**A:** Нет, благодаря tenant scoping:

```php
// routes/channels.php
Broadcast::channel('stream.{streamId}', function ($user, $streamId) {
    $stream = Event::find($streamId);
    
    // User видит только пиров с одинаковым tenant_id
    if ($user->tenant_id !== $stream->tenant_id) {
        return false;  // Запрещено!
    }
    
    return true;
});
```

---

### Q: Где хранятся видеозаписи стримов?

**A:** В базовой реализации **не хранятся**.

Для записи нужно:

```javascript
// Использовать MediaRecorder API
const mediaRecorder = new MediaRecorder(stream);
const chunks = [];

mediaRecorder.ondataavailable = (e) => {
    chunks.push(e.data);
};

mediaRecorder.onstop = () => {
    const blob = new Blob(chunks, { type: 'video/webm' });
    // Отправить blob на сервер
    upload(blob);
};
```

Или использовать сторонний сервис (YouTube Live, Twitch и т.д.).

---

### Q: Как добавить chat в стрим?

**A:** Используйте отдельный канал Reverb:

```javascript
// Frontend
window.Echo.channel(`stream.${streamId}.chat`)
    .listen('ChatMessageSent', (message) => {
        addMessageToChat(message);
    });

// Отправить сообщение
fetch('/streams/chat', {
    method: 'POST',
    body: JSON.stringify({
        stream_id: streamId,
        message: 'Hello everyone!'
    })
});
```

```php
// Backend (ChatController)
broadcast(new ChatMessageSent($streamId, $message));
```

---

### Q: Как добавить gift/donations в стрим?

**A:** Используйте RTCDataChannel:

```javascript
// Создать data channel
const dataChannel = pc.createDataChannel('gifts', {
    ordered: true
});

// Отправить подарок
dataChannel.send(JSON.stringify({
    type: 'gift',
    gift_id: 123,
    to_user: 'master_name',
    amount: 1000
}));

// Получить подарок
pc.ondatachannel = (event) => {
    event.channel.onmessage = (msg) => {
        const data = JSON.parse(msg.data);
        if (data.type === 'gift') {
            showGiftNotification(data);
        }
    };
};
```

---

### Q: Поддерживает ли Safari?

**A:** **Да**, но с ограничениями:
- ✅ iOS 11+ поддерживает WebRTC
- ✅ macOS 11+ поддерживает WebRTC
- ❌ Нет поддержки screen sharing (пока)
- ⚠️ Нет поддержки simulcast (несколько качеств)

**Рекомендация:** Используйте сервис polyfill для старых браузеров.

---

### Q: Как добавить simulcast (несколько качеств видео)?

**A:** Сложная задача. Требуется:

```javascript
// 1. Отправить несколько кодировок
const sender = pc.getSenders().find(s => s.track?.kind === 'video');

await sender.setParameters({
    encodings: [
        { rid: 'h', maxBitrate: 900000 },
        { rid: 'm', maxBitrate: 300000 },
        { rid: 'l', maxBitrate: 100000 }
    ]
});

// 2. Получатель выбирает, какую отправку использовать
```

**Проще:** Используйте SFU (Mediasoup, Janus и т.д.), где SFU сам делает simulcast.

---

### Q: Что такое "NAT"?

**A:** **Network Address Translation** — механизм, который скрывает локальный IP:

```
Ваш компьютер (192.168.1.100)
        ↓
Роутер NAT (192.168.1.1)
        ↓
Интернет (1.2.3.4)

Когда вы отправляете пакет в интернет, роутер переписывает адрес с локального (192.168.1.100)
на глобальный (1.2.3.4). Когда ответ приходит — переписывает обратно.

Проблема P2P: Два peer за NAT не могут найти друг друга без помощника (TURN).
```

**Решение:** TURN сервер работает как "почтальон" между двумя пирами.

---

### Q: Сколько стоит TURN сервер?

**A:**

| Провайдер | Цена | Использование |
|-----------|------|---|
| **coturn (self-hosted)** | $5-20/мес | Лучше всего для production |
| **Google STUN** | Бесплатно | Только STUN (не TURN) |
| **Twilio** | $0.015/GB | Дорого при трафике |
| **xirsys** | $0-99/мес | Хороший баланс |

**Рекомендация:** Используйте **coturn на VPS** за $10/мес с поддержкой до 10,000 пиров.

---

### Q: Как протестировать локально без TURN?

**A:** Если оба пира в одной LAN:

```env
# .env
WEBRTC_STUN=stun:stun.l.google.com:19302
WEBRTC_TURN_URL=  # Оставьте пустым

# Локально P2P будет работать
# В интернете НЕ будет работать без TURN
```

---

## 🔧 Полезные команды

```bash
# Смотреть логи Reverb
tail -f storage/logs/laravel.log | grep -i reverb

# Мониторить peer connections
watch -n 1 'sqlite3 database/database.sqlite "SELECT count(*), status FROM stream_peer_connections GROUP BY status;"'

# Очистить закрытые соединения
php artisan db:seed --class=CleanupClosedConnectionsSeeder

# Запустить background job
php artisan queue:work --queue=default,mesh

# Тестировать ICE
turnutils_uclient -u user -w pass -p 3478 turn-server-ip
```

---

## 📞 Получить помощь

1. **Проверьте логи:**
   - `storage/logs/laravel.log`
   - `storage/logs/reverb.log`
   - Browser console (F12)

2. **Используйте SQL queries:** `WEBRTC_SQL_QUERIES.sql`

3. **Читайте документацию:** `WEBRTC_LIVE_STREAMING_GUIDE.md`

4. **Тестируйте API:** `WEBRTC_POSTMAN_COLLECTION.json`

---

**Версия:** March 2026  
**Последнее обновление:** Сегодня
