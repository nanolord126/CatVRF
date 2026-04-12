<script setup>
/**
 * BeautyChat — полноценный чат для коммуникации с клиентами салона.
 * Каналы: WhatsApp, Telegram, SMS, In-app, Email.
 * Функции: шаблоны сообщений, быстрые ответы, история переписки,
 * привязка к клиенту CRM, отправка файлов, уведомления.
 */
import { ref, computed, reactive, watch, nextTick } from 'vue';
import VCard from '../../UI/VCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';

const props = defineProps({
    clients: { type: Array, default: () => [] },
    masters: { type: Array, default: () => [] },
});
const emit = defineEmits(['open-client', 'book-client', 'award-bonus']);

/* ─── Channels ─── */
const channels = [
    { key: 'all',       label: 'Все',        icon: '💬', color: 'blue' },
    { key: 'whatsapp',  label: 'WhatsApp',   icon: '📱', color: 'green' },
    { key: 'telegram',  label: 'Telegram',   icon: '✈️', color: 'blue' },
    { key: 'sms',       label: 'SMS',        icon: '📲', color: 'purple' },
    { key: 'inapp',     label: 'In-app',     icon: '🔔', color: 'yellow' },
    { key: 'email',     label: 'Email',      icon: '📧', color: 'gray' },
];
const activeChannel = ref('all');

/* ─── Chat List ─── */
const chats = ref([
    { id: 1, clientId: 1, name: 'Мария Кузнецова', phone: '+7 900 111-22-33', avatar: '👩', channel: 'whatsapp', lastMessage: 'Здравствуйте! Хочу записаться на окрашивание', lastTime: '10:32', unread: 2, online: true, segment: 'VIP' },
    { id: 2, clientId: 2, name: 'Елена Петрова', phone: '+7 900 222-33-44', avatar: '👩‍🦰', channel: 'telegram', lastMessage: 'Спасибо за напоминание! Буду вовремя', lastTime: '09:45', unread: 0, online: true, segment: 'Постоянный' },
    { id: 3, clientId: 3, name: 'Дарья Волкова', phone: '+7 900 333-44-55', avatar: '👩‍🦱', channel: 'whatsapp', lastMessage: 'Можно перенести на четверг?', lastTime: 'Вчера', unread: 1, online: false, segment: 'Новый' },
    { id: 4, clientId: 4, name: 'Ирина Михайлова', phone: '+7 900 444-55-66', avatar: '👧', channel: 'sms', lastMessage: 'Напоминание: запись завтра в 11:30', lastTime: 'Вчера', unread: 0, online: false, segment: 'Постоянный' },
    { id: 5, clientId: 5, name: 'Наталья Белова', phone: '+7 900 555-66-77', avatar: '👩‍🦳', channel: 'telegram', lastMessage: 'Сколько стоит ламинирование бровей?', lastTime: '07.04', unread: 1, online: false, segment: 'Спящий' },
    { id: 6, clientId: 6, name: 'Виктория Новикова', phone: '+7 900 666-77-88', avatar: '💁‍♀️', channel: 'email', lastMessage: 'Прайс-лист на корпоративные услуги', lastTime: '06.04', unread: 0, online: false, segment: 'B2B' },
    { id: 7, clientId: 7, name: 'Регина Козлова', phone: '+7 900 777-88-99', avatar: '👩‍🎤', channel: 'inapp', lastMessage: 'Оставила отзыв ⭐⭐⭐⭐⭐', lastTime: '06.04', unread: 0, online: false, segment: 'VIP' },
    { id: 8, clientId: 8, name: 'Алиса Тихонова', phone: '+7 900 888-99-00', avatar: '👱‍♀️', channel: 'whatsapp', lastMessage: 'Хочу отменить запись', lastTime: '05.04', unread: 0, online: false, segment: 'Постоянный' },
]);

const searchQuery = ref('');
const activeChatId = ref(null);

const filteredChats = computed(() => {
    let list = chats.value;
    if (activeChannel.value !== 'all') {
        list = list.filter(c => c.channel === activeChannel.value);
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(c => c.name.toLowerCase().includes(q) || c.phone.includes(q) || c.lastMessage.toLowerCase().includes(q));
    }
    return list.sort((a, b) => (b.unread || 0) - (a.unread || 0));
});

const totalUnread = computed(() => chats.value.reduce((sum, c) => sum + (c.unread || 0), 0));

const activeChat = computed(() => chats.value.find(c => c.id === activeChatId.value));

/* ─── Messages ─── */
const messages = ref({
    1: [
        { id: 1, from: 'client', text: 'Здравствуйте! Хочу записаться на окрашивание AirTouch', time: '10:28', status: 'read' },
        { id: 2, from: 'operator', text: 'Добрый день, Мария! Конечно, у нас есть свободные слоты. Когда вам удобно?', time: '10:30', status: 'read' },
        { id: 3, from: 'client', text: 'В пятницу после обеда было бы идеально', time: '10:31', status: 'read' },
        { id: 4, from: 'client', text: 'И ещё хотела бы узнать цену', time: '10:32', status: 'delivered' },
    ],
    2: [
        { id: 1, from: 'operator', text: 'Елена, напоминаем: запись на маникюр завтра в 10:30. Мастер: Ольга Д.', time: '09:40', status: 'read' },
        { id: 2, from: 'client', text: 'Спасибо за напоминание! Буду вовремя', time: '09:45', status: 'read' },
    ],
    3: [
        { id: 1, from: 'client', text: 'Добрый день! У меня запись на среду, но возникли обстоятельства', time: '16:20', status: 'read' },
        { id: 2, from: 'client', text: 'Можно перенести на четверг?', time: '16:21', status: 'delivered' },
    ],
    5: [
        { id: 1, from: 'client', text: 'Сколько стоит ламинирование бровей?', time: '14:10', status: 'delivered' },
    ],
});

const newMessage = ref('');
const messageListRef = ref(null);

const currentMessages = computed(() => {
    if (!activeChatId.value) return [];
    return messages.value[activeChatId.value] || [];
});

function openChat(chat) {
    activeChatId.value = chat.id;
    chat.unread = 0;
    nextTick(() => scrollToBottom());
}

function scrollToBottom() {
    if (messageListRef.value) {
        messageListRef.value.scrollTop = messageListRef.value.scrollHeight;
    }
}

function sendMessage() {
    if (!newMessage.value.trim() || !activeChatId.value) return;
    const chatMsgs = messages.value[activeChatId.value] || [];
    const msg = {
        id: Date.now(),
        from: 'operator',
        text: newMessage.value.trim(),
        time: new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }),
        status: 'sent',
    };
    chatMsgs.push(msg);
    messages.value[activeChatId.value] = chatMsgs;

    const chat = chats.value.find(c => c.id === activeChatId.value);
    if (chat) {
        chat.lastMessage = msg.text;
        chat.lastTime = msg.time;
    }
    newMessage.value = '';
    nextTick(() => scrollToBottom());

    /* Simulate delivery status update */
    setTimeout(() => { msg.status = 'delivered'; }, 800);
    setTimeout(() => { msg.status = 'read'; }, 2500);
}

/* ─── Templates ─── */
const showTemplates = ref(false);
const messageTemplates = ref([
    { id: 1, name: '📅 Напоминание о записи', text: 'Здравствуйте, {имя}! Напоминаем о записи {дата} в {время}. Мастер: {мастер}. Ждём вас! 💇‍♀️', category: 'booking' },
    { id: 2, name: '✅ Подтверждение записи', text: '{имя}, ваша запись подтверждена: {услуга} {дата} в {время}. Мастер: {мастер}. До встречи!', category: 'booking' },
    { id: 3, name: '🎁 Бонусы начислены', text: '{имя}, вам начислено {сумма} бонусных рублей! Баланс: {баланс} ₽. Потратьте на следующем визите 💝', category: 'loyalty' },
    { id: 4, name: '🔥 Специальное предложение', text: '{имя}, специально для вас: {акция}! Действует до {дата}. Запишитесь: {ссылка}', category: 'promo' },
    { id: 5, name: '⭐ Просьба об отзыве', text: '{имя}, спасибо за визит! Будем рады вашему отзыву: {ссылка}. Это займёт 1 минуту 🙏', category: 'review' },
    { id: 6, name: '💔 Возвращение клиента', text: '{имя}, мы скучаем! Дарим скидку {скидка}% на следующий визит. Запишитесь: {ссылка} 💇‍♀️', category: 'retention' },
    { id: 7, name: '🎂 Поздравление с ДР', text: '{имя}, с днём рождения! 🎉 Дарим {сумма} бонусных рублей! Действует 30 дней 🎁', category: 'birthday' },
    { id: 8, name: '❌ Отмена записи', text: '{имя}, запись на {дата} в {время} отменена. Если хотите перенести — напишите нам или позвоните 📞', category: 'booking' },
]);
const templateSearch = ref('');
const filteredTemplates = computed(() => {
    if (!templateSearch.value.trim()) return messageTemplates.value;
    const q = templateSearch.value.toLowerCase();
    return messageTemplates.value.filter(t => t.name.toLowerCase().includes(q) || t.text.toLowerCase().includes(q));
});

function useTemplate(tpl) {
    let text = tpl.text;
    if (activeChat.value) {
        text = text.replace('{имя}', activeChat.value.name.split(' ')[0]);
    }
    newMessage.value = text;
    showTemplates.value = false;
}

/* ─── Quick replies ─── */
const quickReplies = [
    '👍 Отлично, записала!',
    '📅 Когда вам удобно?',
    '💰 Стоимость услуги: ',
    '⏰ Свободные слоты: ',
    '🔄 Давайте перенесём',
    '✅ Подтверждаю запись',
];

function insertQuickReply(reply) {
    newMessage.value = reply;
}

/* ─── Attachments ─── */
const fileInput = ref(null);
const attachments = ref([]);

function triggerFileUpload() {
    fileInput.value?.click();
}
function handleFileUpload(e) {
    const files = e.target.files;
    if (!files?.length) return;
    for (const file of files) {
        attachments.value.push({ name: file.name, size: file.size, type: file.type });
    }
    sendFileMessage(files[0]);
}
function sendFileMessage(file) {
    if (!activeChatId.value) return;
    const chatMsgs = messages.value[activeChatId.value] || [];
    chatMsgs.push({
        id: Date.now(),
        from: 'operator',
        text: `📎 ${file.name} (${(file.size / 1024).toFixed(1)} KB)`,
        time: new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }),
        status: 'sent',
        isFile: true,
    });
    messages.value[activeChatId.value] = chatMsgs;
    nextTick(() => scrollToBottom());
}

/* ─── Bulk messaging ─── */
const showBulkMessage = ref(false);
const bulkMessage = reactive({
    channel: 'whatsapp',
    template: null,
    customText: '',
    segment: 'all',
    scheduledAt: '',
});
const bulkSegments = [
    { key: 'all', label: 'Все клиенты' },
    { key: 'vip', label: 'VIP' },
    { key: 'regular', label: 'Постоянные' },
    { key: 'new', label: 'Новые' },
    { key: 'sleeping', label: 'Спящие' },
    { key: 'b2b', label: 'B2B' },
];

function sendBulkMessage() {
    const text = bulkMessage.template
        ? messageTemplates.value.find(t => t.id === bulkMessage.template)?.text || bulkMessage.customText
        : bulkMessage.customText;
    const targetClients = props.clients.filter(c => {
        if (bulkMessage.segment === 'all') return true;
        return (c.segment || '').toLowerCase() === bulkMessage.segment;
    });
    const sentCount = targetClients.length || chats.value.length;
    const notification = {
        id: Date.now(),
        type: 'bulk_sent',
        text: `Рассылка отправлена: ${sentCount} получателей через ${bulkMessage.channel}`,
        time: new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }),
    };
    bulkHistory.value.unshift(notification);
    showBulkMessage.value = false;
    Object.assign(bulkMessage, { channel: 'whatsapp', template: null, customText: '', segment: 'all', scheduledAt: '' });
}

const bulkHistory = ref([
    { id: 1, type: 'bulk_sent', text: 'Рассылка «Весенние скидки»: 245 получателей через WhatsApp', time: '07.04 15:00', stats: { delivered: 238, read: 192, clicked: 67 } },
    { id: 2, type: 'bulk_sent', text: 'Напоминание о записях: 18 получателей через SMS', time: '08.04 08:00', stats: { delivered: 18, read: 18, clicked: 12 } },
]);

/* ─── Chat info sidebar ─── */
const showChatInfo = ref(false);
const channelIcon = { whatsapp: '📱', telegram: '✈️', sms: '📲', inapp: '🔔', email: '📧' };
const channelLabel = { whatsapp: 'WhatsApp', telegram: 'Telegram', sms: 'SMS', inapp: 'In-app', email: 'Email' };
const statusIcon = { sent: '✓', delivered: '✓✓', read: '✓✓' };
const statusColor = { sent: 'var(--t-text-3)', delivered: 'var(--t-text-3)', read: 'var(--t-primary)' };
const segmentColors = { VIP: 'purple', 'Постоянный': 'blue', 'Новый': 'green', 'Спящий': 'yellow', B2B: 'gray' };

function handleKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }
</script>

<template>
<div class="flex h-[calc(100vh-220px)] min-h-[600px] rounded-2xl overflow-hidden border" style="border-color:var(--t-border)">

    <!-- LEFT: Chat List -->
    <div class="w-80 shrink-0 flex flex-col border-r" style="background:var(--t-surface);border-color:var(--t-border)">
        <!-- Header -->
        <div class="p-4 border-b" style="border-color:var(--t-border)">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold" style="color:var(--t-text)">💬 Чат</h2>
                <div class="flex gap-2">
                    <VBadge v-if="totalUnread > 0" color="red" size="sm">{{ totalUnread }}</VBadge>
                    <VButton size="sm" variant="outline" @click="showBulkMessage = true">📢</VButton>
                </div>
            </div>
            <input v-model="searchQuery" type="text" placeholder="Поиск по имени, телефону..." class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)" />
        </div>
        <!-- Channel tabs -->
        <div class="flex overflow-x-auto px-2 py-2 gap-1 border-b" style="border-color:var(--t-border)">
            <button v-for="ch in channels" :key="ch.key" @click="activeChannel = ch.key"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-all"
                    :style="activeChannel === ch.key ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-bg);color:var(--t-text-2)'">
                {{ ch.icon }} {{ ch.label }}
            </button>
        </div>
        <!-- Chat list -->
        <div class="flex-1 overflow-y-auto">
            <div v-for="chat in filteredChats" :key="chat.id" @click="openChat(chat)"
                 class="flex items-center gap-3 p-3 cursor-pointer border-b transition-all"
                 :style="activeChatId === chat.id
                    ? 'background:var(--t-primary-dim);border-color:var(--t-border)'
                    : 'border-color:var(--t-border);background:transparent'"
                 @mouseenter="$event.target.style.background = activeChatId === chat.id ? 'var(--t-primary-dim)' : 'var(--t-bg)'"
                 @mouseleave="$event.target.style.background = activeChatId === chat.id ? 'var(--t-primary-dim)' : 'transparent'">
                <div class="relative">
                    <span class="text-2xl">{{ chat.avatar }}</span>
                    <span v-if="chat.online" class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2" style="border-color:var(--t-surface)"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-sm truncate" style="color:var(--t-text)">{{ chat.name }}</span>
                        <span class="text-xs" style="color:var(--t-text-3)">{{ chat.lastTime }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-0.5">
                        <span class="text-xs truncate pr-2" style="color:var(--t-text-2)">{{ chat.lastMessage }}</span>
                        <div class="flex items-center gap-1">
                            <span class="text-xs">{{ channelIcon[chat.channel] }}</span>
                            <VBadge v-if="chat.unread > 0" color="red" size="sm">{{ chat.unread }}</VBadge>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="filteredChats.length === 0" class="p-6 text-center text-sm" style="color:var(--t-text-3)">
                Нет чатов
            </div>
        </div>
    </div>

    <!-- CENTER: Messages -->
    <div class="flex-1 flex flex-col" style="background:var(--t-bg)">
        <template v-if="activeChat">
            <!-- Chat Header -->
            <div class="flex items-center justify-between p-4 border-b" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ activeChat.avatar }}</span>
                    <div>
                        <div class="font-bold text-sm" style="color:var(--t-text)">{{ activeChat.name }}</div>
                        <div class="flex items-center gap-2 text-xs" style="color:var(--t-text-2)">
                            <span>{{ channelIcon[activeChat.channel] }} {{ channelLabel[activeChat.channel] }}</span>
                            <span>·</span>
                            <VBadge :color="segmentColors[activeChat.segment] || 'gray'" size="sm">{{ activeChat.segment }}</VBadge>
                            <span v-if="activeChat.online" class="text-green-500">● онлайн</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <VButton size="sm" variant="outline" @click="emit('open-client', activeChat.clientId)">👤 Профиль</VButton>
                    <VButton size="sm" variant="outline" @click="emit('book-client', activeChat.clientId)">📅 Записать</VButton>
                    <VButton size="sm" variant="outline" @click="showChatInfo = !showChatInfo">ℹ️</VButton>
                </div>
            </div>

            <!-- Messages -->
            <div ref="messageListRef" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div v-for="msg in currentMessages" :key="msg.id"
                     class="flex" :class="msg.from === 'operator' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[70%] rounded-2xl px-4 py-2.5 text-sm"
                         :style="msg.from === 'operator'
                            ? 'background:var(--t-primary);color:#fff;border-bottom-right-radius:4px'
                            : 'background:var(--t-surface);color:var(--t-text);border-bottom-left-radius:4px;border:1px solid var(--t-border)'">
                        <div>{{ msg.text }}</div>
                        <div class="flex items-center justify-end gap-1 mt-1" :style="msg.from === 'operator' ? 'color:rgba(255,255,255,0.7)' : 'color:var(--t-text-3)'">
                            <span class="text-[10px]">{{ msg.time }}</span>
                            <span v-if="msg.from === 'operator'" class="text-[10px]" :style="`color:${statusColor[msg.status]}`">{{ statusIcon[msg.status] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Replies -->
            <div class="flex overflow-x-auto px-4 py-2 gap-2 border-t" style="border-color:var(--t-border)">
                <button v-for="qr in quickReplies" :key="qr" @click="insertQuickReply(qr)"
                        class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap border transition-all"
                        style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text-2)"
                        @mouseenter="$event.target.style.borderColor = 'var(--t-primary)'"
                        @mouseleave="$event.target.style.borderColor = 'var(--t-border)'">
                    {{ qr }}
                </button>
            </div>

            <!-- Input -->
            <div class="p-4 border-t" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-end gap-3">
                    <div class="flex gap-1">
                        <button @click="triggerFileUpload" class="p-2 rounded-lg transition-all" style="color:var(--t-text-2)" title="Файл">📎</button>
                        <button @click="showTemplates = true" class="p-2 rounded-lg transition-all" style="color:var(--t-text-2)" title="Шаблоны">📋</button>
                        <input ref="fileInput" type="file" class="hidden" @change="handleFileUpload" accept="image/*,.pdf,.doc,.docx" />
                    </div>
                    <textarea v-model="newMessage" @keydown="handleKeydown" rows="1" placeholder="Введите сообщение..."
                              class="flex-1 rounded-xl px-4 py-3 text-sm border resize-none"
                              style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text);min-height:44px;max-height:120px"></textarea>
                    <VButton @click="sendMessage" :disabled="!newMessage.trim()">➤</VButton>
                </div>
            </div>
        </template>

        <!-- Empty state -->
        <div v-else class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-6xl mb-4">💬</div>
                <h3 class="text-lg font-semibold mb-2" style="color:var(--t-text)">Выберите чат</h3>
                <p class="text-sm" style="color:var(--t-text-2)">Выберите диалог из списка слева или начните новый</p>
                <VButton class="mt-4" variant="outline" @click="showBulkMessage = true">📢 Массовая рассылка</VButton>
            </div>
        </div>
    </div>

    <!-- RIGHT: Chat Info Sidebar -->
    <div v-if="showChatInfo && activeChat" class="w-72 shrink-0 border-l overflow-y-auto" style="background:var(--t-surface);border-color:var(--t-border)">
        <div class="p-4 text-center border-b" style="border-color:var(--t-border)">
            <span class="text-5xl block mb-3">{{ activeChat.avatar }}</span>
            <h3 class="font-bold" style="color:var(--t-text)">{{ activeChat.name }}</h3>
            <p class="text-xs mt-1" style="color:var(--t-text-2)">{{ activeChat.phone }}</p>
            <VBadge :color="segmentColors[activeChat.segment] || 'gray'" size="sm" class="mt-2">{{ activeChat.segment }}</VBadge>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <h4 class="text-xs font-semibold uppercase mb-2" style="color:var(--t-text-3)">Канал связи</h4>
                <div class="flex items-center gap-2 text-sm" style="color:var(--t-text)">
                    <span>{{ channelIcon[activeChat.channel] }}</span>
                    <span>{{ channelLabel[activeChat.channel] }}</span>
                </div>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase mb-2" style="color:var(--t-text-3)">Действия</h4>
                <div class="space-y-2">
                    <VButton size="sm" variant="outline" class="w-full" @click="emit('open-client', activeChat.clientId)">👤 Открыть профиль</VButton>
                    <VButton size="sm" variant="outline" class="w-full" @click="emit('book-client', activeChat.clientId)">📅 Записать</VButton>
                    <VButton size="sm" variant="outline" class="w-full" @click="emit('award-bonus', activeChat.clientId)">🎁 Начислить бонус</VButton>
                </div>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase mb-2" style="color:var(--t-text-3)">Статистика переписки</h4>
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div class="text-lg font-bold" style="color:var(--t-primary)">{{ currentMessages.length }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">Сообщений</div>
                    </div>
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div class="text-lg font-bold" style="color:var(--t-primary)">{{ currentMessages.filter(m => m.from === 'operator').length }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">Наших</div>
                    </div>
                </div>
            </div>
            <!-- Bulk history -->
            <div>
                <h4 class="text-xs font-semibold uppercase mb-2" style="color:var(--t-text-3)">История рассылок</h4>
                <div class="space-y-2">
                    <div v-for="bh in bulkHistory.slice(0, 3)" :key="bh.id" class="p-2 rounded-lg text-xs" style="background:var(--t-bg)">
                        <div style="color:var(--t-text)">{{ bh.text }}</div>
                        <div class="flex gap-2 mt-1" style="color:var(--t-text-3)">
                            <span>{{ bh.time }}</span>
                            <span v-if="bh.stats">📬 {{ bh.stats.delivered }} · 👁 {{ bh.stats.read }} · 👆 {{ bh.stats.clicked }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Templates Modal -->
<VModal :show="showTemplates" @close="showTemplates = false" title="📋 Шаблоны сообщений" size="lg">
    <div class="space-y-4">
        <input v-model="templateSearch" type="text" placeholder="Поиск шаблона..." class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)" />
        <div class="space-y-2 max-h-[400px] overflow-y-auto">
            <div v-for="tpl in filteredTemplates" :key="tpl.id"
                 @click="useTemplate(tpl)"
                 class="p-3 rounded-xl border cursor-pointer transition-all"
                 style="background:var(--t-bg);border-color:var(--t-border)"
                 @mouseenter="$event.target.style.borderColor = 'var(--t-primary)'"
                 @mouseleave="$event.target.style.borderColor = 'var(--t-border)'">
                <div class="font-medium text-sm mb-1" style="color:var(--t-text)">{{ tpl.name }}</div>
                <div class="text-xs" style="color:var(--t-text-2)">{{ tpl.text }}</div>
            </div>
        </div>
    </div>
</VModal>

<!-- Bulk Message Modal -->
<VModal :show="showBulkMessage" @close="showBulkMessage = false" title="📢 Массовая рассылка" size="lg">
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Канал</label>
                <select v-model="bulkMessage.channel" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)">
                    <option v-for="ch in channels.filter(c => c.key !== 'all')" :key="ch.key" :value="ch.key">{{ ch.icon }} {{ ch.label }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Сегмент</label>
                <select v-model="bulkMessage.segment" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)">
                    <option v-for="s in bulkSegments" :key="s.key" :value="s.key">{{ s.label }}</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Шаблон (необязательно)</label>
            <select v-model="bulkMessage.template" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)">
                <option :value="null">— Свой текст —</option>
                <option v-for="tpl in messageTemplates" :key="tpl.id" :value="tpl.id">{{ tpl.name }}</option>
            </select>
        </div>
        <div v-if="!bulkMessage.template">
            <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Текст рассылки</label>
            <textarea v-model="bulkMessage.customText" rows="4" class="w-full rounded-lg px-3 py-2 text-sm border resize-none" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)" placeholder="Введите текст рассылки..."></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Запланировать (необязательно)</label>
            <input v-model="bulkMessage.scheduledAt" type="datetime-local" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)" />
        </div>
        <div class="flex justify-end gap-3">
            <VButton variant="outline" @click="showBulkMessage = false">Отмена</VButton>
            <VButton @click="sendBulkMessage">📢 Отправить</VButton>
        </div>
    </div>
</VModal>

</template>
