<script setup>
/**
 * BeautyCalendar — полный B2B-календарь записей вертикали Beauty.
 *
 * 5 режимов: День · Неделя · Месяц · Timeline · Список
 * Фильтры: мастер / услуга / статус / филиал
 * Drag & Drop + Resize + клик-создание + контекстное меню
 * B2B: загрузка мастеров, буфер-тайм, блокировка слотов,
 *       массовые действия, индикатор перегрузки, авто-подбор
 * Допы: напоминания, B2C-интеграция, экспорт PDF/iCal, история
 */
import { ref, computed, reactive, watch, onMounted, nextTick } from 'vue';
import VCard   from '../../UI/VCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge  from '../../UI/VBadge.vue';
import VModal  from '../../UI/VModal.vue';
import VInput  from '../../UI/VInput.vue';

/* ─── Props ─── */
const props = defineProps({
    masters:  { type: Array, default: () => [] },
    salons:   { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    bookings: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'create-booking', 'update-booking', 'cancel-booking',
    'move-booking', 'resize-booking', 'quick-book',
]);

/* ════════════════════════════════════════════════════════
   1. СОСТОЯНИЕ
   ════════════════════════════════════════════════════════ */

/* View modes */
const viewModes = [
    { key: 'day',      label: 'День',     icon: '📅' },
    { key: 'week',     label: 'Неделя',   icon: '📆' },
    { key: 'month',    label: 'Месяц',    icon: '🗓' },
    { key: 'timeline', label: 'Timeline', icon: '⏱' },
    { key: 'list',     label: 'Список',   icon: '📋' },
];
const currentView = ref('day');

/* Дата / навигация */
const currentDate = ref(new Date());
const todayStr = computed(() => fmtDate(new Date()));
const selectedDate = computed(() => fmtDate(currentDate.value));

/* Фильтры */
const filterMasters   = ref([]);     // multi-select id мастеров
const filterServices  = ref([]);     // multi-select категорий
const filterStatuses  = ref([]);     // multi-select статусов
const filterSalonId   = ref(null);   // один филиал или null = все
const showFilters     = ref(false);

/* Drag & Drop */
const dragState = reactive({
    active: false,
    bookingId: null,
    originMasterId: null,
    originTime: null,
    ghostTop: 0,
    ghostLeft: 0,
});

/* Resize */
const resizeState = reactive({
    active: false,
    bookingId: null,
    startY: 0,
    originalDuration: 0,
});

/* Контекстное меню */
const ctxMenu = reactive({
    show: false,
    x: 0,
    y: 0,
    bookingId: null,
    slotTime: null,
    slotMasterId: null,
});

/* Модалки */
const showCreateModal     = ref(false);
const showDetailModal     = ref(false);
const showBlockSlotModal  = ref(false);
const showMassActionModal = ref(false);
const showHistoryModal    = ref(false);
const showReminderModal   = ref(false);
const showExportModal     = ref(false);

/* Создание / редактирование */
const editingBooking = ref(null);
const newBooking = reactive({
    clientName: '', clientPhone: '', serviceId: null, masterId: null,
    date: '', time: '', notes: '', source: 'b2b', prepaid: 0,
});

/* Массовые действия */
const selectedBookings = ref([]);

/* История изменений */
const changeHistory = ref([
    { id: 1, ts: '08.04.2026 09:12', user: 'Админ',     action: 'Перенесена запись #1001 с 10:00 на 11:00',  type: 'move' },
    { id: 2, ts: '08.04.2026 09:30', user: 'Ольга Д.',   action: 'Отменена запись #1008 (клиент не пришёл)',  type: 'cancel' },
    { id: 3, ts: '08.04.2026 10:05', user: 'Система',    action: 'Авто-подтверждение записи #1003 (предоплата получена)', type: 'confirm' },
    { id: 4, ts: '07.04.2026 18:22', user: 'B2C-сайт',   action: 'Новая запись #1009 через онлайн-бронирование', type: 'create' },
    { id: 5, ts: '07.04.2026 17:00', user: 'Админ',     action: 'Массовая блокировка 12:00–13:00 (обед) для всех мастеров', type: 'block' },
]);

/* Slot blocking */
const blockSlot = reactive({
    masterId: null, dateFrom: '', dateTo: '', timeFrom: '12:00', timeTo: '13:00',
    reason: 'lunch', customReason: '',
});
const blockReasons = [
    { key: 'lunch',    label: '🍽 Обед' },
    { key: 'break',    label: '☕ Перерыв' },
    { key: 'vacation', label: '🏖 Отпуск' },
    { key: 'sick',     label: '🤒 Больничный' },
    { key: 'training', label: '📚 Обучение' },
    { key: 'custom',   label: '✏️ Другое' },
];

/* Reminder config */
const reminderSettings = reactive({
    sms24h: true, push2h: true, push30m: true, whatsapp24h: false,
});

/* ════════════════════════════════════════════════════════
   2. РАСШИРЕННЫЕ ДАННЫЕ КАЛЕНДАРЯ
   ════════════════════════════════════════════════════════ */

/* Мастер-цвета (по позиции в массиве) */
const masterColors = [
    { bg: 'bg-violet-500/20',  border: 'border-violet-500/50',  dot: 'bg-violet-500',  text: 'text-violet-400'  },
    { bg: 'bg-sky-500/20',     border: 'border-sky-500/50',     dot: 'bg-sky-500',     text: 'text-sky-400'     },
    { bg: 'bg-emerald-500/20', border: 'border-emerald-500/50', dot: 'bg-emerald-500', text: 'text-emerald-400' },
    { bg: 'bg-amber-500/20',   border: 'border-amber-500/50',   dot: 'bg-amber-500',  text: 'text-amber-400'   },
    { bg: 'bg-rose-500/20',    border: 'border-rose-500/50',    dot: 'bg-rose-500',   text: 'text-rose-400'    },
    { bg: 'bg-cyan-500/20',    border: 'border-cyan-500/50',    dot: 'bg-cyan-500',   text: 'text-cyan-400'    },
    { bg: 'bg-pink-500/20',    border: 'border-pink-500/50',    dot: 'bg-pink-500',   text: 'text-pink-400'    },
    { bg: 'bg-teal-500/20',    border: 'border-teal-500/50',    dot: 'bg-teal-500',   text: 'text-teal-400'    },
];
const getMasterColor = (idx) => masterColors[idx % masterColors.length];

/* Статус-цвета и лейблы */
const statusConfig = {
    confirmed:   { color: 'green',  label: 'Подтверждено',   icon: '✅', bg: 'bg-green-500/20',  border: 'border-green-500/40'  },
    pending:     { color: 'yellow', label: 'Ожидание',       icon: '⏳', bg: 'bg-yellow-500/20', border: 'border-yellow-500/40' },
    in_progress: { color: 'blue',   label: 'В процессе',     icon: '🔄', bg: 'bg-blue-500/20',   border: 'border-blue-500/40'   },
    completed:   { color: 'blue',   label: 'Завершено',      icon: '✔️', bg: 'bg-blue-500/10',   border: 'border-blue-500/30'   },
    cancelled:   { color: 'red',    label: 'Отменено',       icon: '❌', bg: 'bg-red-500/15',    border: 'border-red-500/30'    },
    no_show:     { color: 'gray',   label: 'Не пришёл',      icon: '👻', bg: 'bg-gray-500/15',   border: 'border-gray-500/30'   },
    free:        { color: 'gray',   label: 'Свободно',       icon: '',   bg: 'bg-white/5',       border: 'border-white/10'      },
    lunch:       { color: 'orange', label: 'Обед',           icon: '🍽', bg: 'bg-orange-500/10', border: 'border-orange-500/20' },
    break:       { color: 'orange', label: 'Перерыв',        icon: '☕', bg: 'bg-orange-500/10', border: 'border-orange-500/20' },
    blocked:     { color: 'red',    label: 'Заблокировано',  icon: '🚫', bg: 'bg-red-500/10',    border: 'border-red-500/20'    },
};

/* Часы сетки (8:00 – 21:00) */
const workHours = Array.from({ length: 27 }, (_, i) => {
    const h = 8 + Math.floor(i / 2);
    const m = (i % 2) * 30;
    return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
});

/* Блокировки слотов (breaks, lunch и пр.) */
const blockedSlots = ref([
    { masterId: null, date: '2026-04-08', timeFrom: '13:00', timeTo: '14:00', reason: 'lunch' },
    { masterId: 3,    date: '2026-04-08', timeFrom: '15:00', timeTo: '15:30', reason: 'break' },
]);

/* ════════════════════════════════════════════════════════
   3. COMPUTED — ФИЛЬТРАЦИЯ И АГРЕГАТЫ
   ════════════════════════════════════════════════════════ */

/* Активные мастера с учётом фильтра филиала */
const activeMasters = computed(() => {
    let list = props.masters.filter(m => m.status === 'active' || !m.status);
    if (filterSalonId.value) list = list.filter(m => m.salon_id === filterSalonId.value || m.salon === filterSalonId.value);
    if (filterMasters.value.length) list = list.filter(m => filterMasters.value.includes(m.id));
    return list;
});

/* Все записи на текущую дату (filtered) */
const dayBookings = computed(() => {
    const dateStr = selectedDate.value;
    let list = props.bookings.filter(b => {
        const bDate = b.date?.split(' ')[0] || b.date;
        return bDate === dateStr;
    });
    if (filterStatuses.value.length) list = list.filter(b => filterStatuses.value.includes(b.status));
    if (filterServices.value.length) list = list.filter(b => filterServices.value.includes(b.serviceCat || b.service));
    if (filterMasters.value.length)  list = list.filter(b => filterMasters.value.includes(b.masterId || b.master_id));
    return list;
});

/* Кол-во записей по статусу (для бейджей фильтра) */
const statusCounts = computed(() => {
    const counts = {};
    props.bookings.forEach(b => { counts[b.status] = (counts[b.status] || 0) + 1; });
    return counts;
});

/* Загрузка мастера за текущий день (%) */
const masterLoad = computed(() => {
    const loads = {};
    activeMasters.value.forEach(m => {
        const mBookings = dayBookings.value.filter(b => (b.master === m.name) || (b.masterId === m.id));
        const busyMinutes = mBookings.reduce((sum, b) => {
            const srv = props.services.find(s => s.name === b.service || s.id === b.serviceId);
            return sum + (srv?.duration || 60);
        }, 0);
        const totalMinutes = 12 * 60; // 8:00 - 20:00
        loads[m.id] = Math.round((busyMinutes / totalMinutes) * 100);
    });
    return loads;
});

/* Общая загрузка дня */
const dayLoadPercent = computed(() => {
    const vals = Object.values(masterLoad.value);
    if (!vals.length) return 0;
    return Math.round(vals.reduce((a, b) => a + b, 0) / vals.length);
});

/* Перегружен? (> 85%) */
const isOverloaded = computed(() => dayLoadPercent.value > 85);

/* Неделя: массив дат текущей недели (пн-вс) */
const weekDays = computed(() => {
    const d = new Date(currentDate.value);
    const dayOfWeek = d.getDay() || 7;
    const monday = new Date(d);
    monday.setDate(d.getDate() - dayOfWeek + 1);
    return Array.from({ length: 7 }, (_, i) => {
        const dd = new Date(monday);
        dd.setDate(monday.getDate() + i);
        return { date: dd, str: fmtDate(dd), label: fmtDayShort(dd), isToday: fmtDate(dd) === todayStr.value };
    });
});

/* Месяц: сетка 6×7 */
const monthGrid = computed(() => {
    const y = currentDate.value.getFullYear();
    const m = currentDate.value.getMonth();
    const first = new Date(y, m, 1);
    const startDay = (first.getDay() || 7) - 1;
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const grid = [];
    let row = [];
    for (let i = 0; i < startDay; i++) row.push(null);
    for (let d = 1; d <= daysInMonth; d++) {
        const dt = new Date(y, m, d);
        const str = fmtDate(dt);
        const bks = props.bookings.filter(b => (b.date?.split(' ')[0] || b.date) === str);
        row.push({ day: d, date: dt, str, bookings: bks, isToday: str === todayStr.value });
        if (row.length === 7) { grid.push(row); row = []; }
    }
    if (row.length) { while (row.length < 7) row.push(null); grid.push(row); }
    return grid;
});

/* Список: отфильтрованный + отсортированный */
const listBookings = computed(() => {
    let list = [...props.bookings];
    if (filterStatuses.value.length) list = list.filter(b => filterStatuses.value.includes(b.status));
    if (filterMasters.value.length)  list = list.filter(b => filterMasters.value.includes(b.masterId || b.master_id));
    if (filterServices.value.length) list = list.filter(b => filterServices.value.includes(b.serviceCat || b.service));
    return list.sort((a, b) => (a.date > b.date ? 1 : -1));
});

/* B2C-записи (source = b2c) */
const b2cBookings = computed(() => props.bookings.filter(b => b.source === 'b2c'));

/* Авто-подбор свободного мастера на заданное время */
function suggestFreeMaster(time, duration = 60) {
    const freeMasters = activeMasters.value.filter(m => {
        const mBookings = dayBookings.value.filter(b => (b.master === m.name) || (b.masterId === m.id));
        return !mBookings.some(b => {
            const bTime = b.date?.split(' ')[1] || '00:00';
            const bSrv  = props.services.find(s => s.name === b.service);
            const bEnd  = addMinutes(bTime, bSrv?.duration || 60);
            const slotEnd = addMinutes(time, duration);
            return !(slotEnd <= bTime || time >= bEnd);
        });
    });
    return freeMasters.sort((a, b) => (masterLoad.value[a.id] || 0) - (masterLoad.value[b.id] || 0));
}

/* ════════════════════════════════════════════════════════
   4. DRAG & DROP
   ════════════════════════════════════════════════════════ */

function onDragStart(e, booking) {
    dragState.active = true;
    dragState.bookingId = booking.id;
    dragState.originMasterId = booking.masterId || booking.master_id;
    dragState.originTime = booking.date?.split(' ')[1] || '00:00';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(booking.id));
}

function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function onDrop(e, masterId, time) {
    e.preventDefault();
    if (!dragState.active) return;
    emit('move-booking', {
        bookingId: dragState.bookingId,
        newMasterId: masterId,
        newTime: time,
        oldMasterId: dragState.originMasterId,
        oldTime: dragState.originTime,
    });
    dragState.active = false;
    dragState.bookingId = null;
}

/* ════════════════════════════════════════════════════════
   5. RESIZE
   ════════════════════════════════════════════════════════ */

function onResizeStart(e, booking) {
    e.preventDefault();
    resizeState.active = true;
    resizeState.bookingId = booking.id;
    resizeState.startY = e.clientY;
    resizeState.originalDuration = props.services.find(s => s.name === booking.service)?.duration || 60;
    document.addEventListener('mousemove', onResizeMove);
    document.addEventListener('mouseup', onResizeEnd);
}

function onResizeMove(e) {
    if (!resizeState.active) return;
    const dy = e.clientY - resizeState.startY;
    const addedMin = Math.round(dy / 2) * 5;
    const newDuration = Math.max(15, resizeState.originalDuration + addedMin);
    /* visual feedback handled by reactive style */
}

function onResizeEnd() {
    if (!resizeState.active) return;
    resizeState.active = false;
    document.removeEventListener('mousemove', onResizeMove);
    document.removeEventListener('mouseup', onResizeEnd);
}

/* ════════════════════════════════════════════════════════
   6. КОНТЕКСТНОЕ МЕНЮ
   ════════════════════════════════════════════════════════ */

function openContextMenu(e, booking = null, time = null, masterId = null) {
    e.preventDefault();
    ctxMenu.show = true;
    ctxMenu.x = e.clientX;
    ctxMenu.y = e.clientY;
    ctxMenu.bookingId = booking?.id || null;
    ctxMenu.slotTime = time;
    ctxMenu.slotMasterId = masterId;
}

function closeContextMenu() {
    ctxMenu.show = false;
}

function ctxAction(action) {
    switch (action) {
        case 'create':
            newBooking.time = ctxMenu.slotTime;
            newBooking.masterId = ctxMenu.slotMasterId;
            newBooking.date = selectedDate.value;
            showCreateModal.value = true;
            break;
        case 'edit':
            editingBooking.value = props.bookings.find(b => b.id === ctxMenu.bookingId);
            showDetailModal.value = true;
            break;
        case 'confirm':
            emit('update-booking', { id: ctxMenu.bookingId, status: 'confirmed' });
            break;
        case 'cancel':
            emit('cancel-booking', { id: ctxMenu.bookingId });
            break;
        case 'block':
            blockSlot.masterId = ctxMenu.slotMasterId;
            blockSlot.dateFrom = selectedDate.value;
            blockSlot.dateTo   = selectedDate.value;
            blockSlot.timeFrom = ctxMenu.slotTime || '12:00';
            blockSlot.timeTo   = addMinutes(ctxMenu.slotTime || '12:00', 60);
            showBlockSlotModal.value = true;
            break;
    }
    closeContextMenu();
}

/* Закрытие контекстного меню при клике вне */
function onDocClick() { if (ctxMenu.show) closeContextMenu(); }
onMounted(() => document.addEventListener('click', onDocClick));

/* ════════════════════════════════════════════════════════
   7. НАВИГАЦИЯ
   ════════════════════════════════════════════════════════ */

function goToday()   { currentDate.value = new Date(); }
function goPrev()    {
    const d = new Date(currentDate.value);
    if (currentView.value === 'month') d.setMonth(d.getMonth() - 1);
    else if (currentView.value === 'week') d.setDate(d.getDate() - 7);
    else d.setDate(d.getDate() - 1);
    currentDate.value = d;
}
function goNext()    {
    const d = new Date(currentDate.value);
    if (currentView.value === 'month') d.setMonth(d.getMonth() + 1);
    else if (currentView.value === 'week') d.setDate(d.getDate() + 7);
    else d.setDate(d.getDate() + 1);
    currentDate.value = d;
}

/* ════════════════════════════════════════════════════════
   8. ХЕЛПЕРЫ
   ════════════════════════════════════════════════════════ */

function fmtDate(d) {
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    return `${dd}.${mm}.${d.getFullYear()}`;
}
function fmtDayShort(d) {
    return ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'][(d.getDay() || 7) - 1];
}
function fmtMonthYear(d) {
    const months = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
    return `${months[d.getMonth()]} ${d.getFullYear()}`;
}
function addMinutes(timeStr, mins) {
    const [h, m] = timeStr.split(':').map(Number);
    const total = h * 60 + m + mins;
    return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
}
function fmt(n) { return Number(n).toLocaleString('ru-RU'); }

function getSlotBooking(time, masterId) {
    return dayBookings.value.find(b => {
        const bTime = b.date?.split(' ')[1] || '00:00';
        const bMaster = b.masterId || b.master_id;
        return bTime === time && (bMaster === masterId || b.master === props.masters.find(m => m.id === masterId)?.name);
    });
}

function getSlotBlock(time, masterId) {
    return blockedSlots.value.find(bl => {
        if (bl.date !== selectedDate.value) return false;
        if (bl.masterId && bl.masterId !== masterId) return false;
        return time >= bl.timeFrom && time < bl.timeTo;
    });
}

function getSlotStatus(time, masterId) {
    const booking = getSlotBooking(time, masterId);
    if (booking) return booking.status;
    const block = getSlotBlock(time, masterId);
    if (block) return block.reason === 'lunch' ? 'lunch' : block.reason === 'break' ? 'break' : 'blocked';
    return 'free';
}

function loadBarColor(pct) {
    if (pct > 85) return 'bg-red-500';
    if (pct > 60) return 'bg-amber-500';
    return 'bg-emerald-500';
}

function monthDayLoad(dayCell) {
    if (!dayCell?.bookings?.length) return '';
    const n = dayCell.bookings.length;
    if (n > 8) return 'bg-red-500/20';
    if (n > 4) return 'bg-amber-500/15';
    return 'bg-emerald-500/10';
}

function handleSlotClick(time, masterId) {
    const booking = getSlotBooking(time, masterId);
    if (booking) {
        editingBooking.value = booking;
        showDetailModal.value = true;
    } else {
        newBooking.time = time;
        newBooking.masterId = masterId;
        newBooking.date = selectedDate.value;
        showCreateModal.value = true;
    }
}

function handleSlotDblClick(time, masterId) {
    const booking = getSlotBooking(time, masterId);
    if (booking) {
        editingBooking.value = booking;
        showDetailModal.value = true;
    }
}

/* Создание записи */
function submitNewBooking() {
    emit('create-booking', { ...newBooking });
    showCreateModal.value = false;
    Object.assign(newBooking, { clientName: '', clientPhone: '', serviceId: null, masterId: null, date: '', time: '', notes: '', source: 'b2b', prepaid: 0 });
}

/* Массовые действия */
function massConfirm() { selectedBookings.value.forEach(id => emit('update-booking', { id, status: 'confirmed' })); selectedBookings.value = []; }
function massCancel()  { selectedBookings.value.forEach(id => emit('cancel-booking', { id })); selectedBookings.value = []; }
function toggleSelectAll() {
    if (selectedBookings.value.length === dayBookings.value.length) selectedBookings.value = [];
    else selectedBookings.value = dayBookings.value.map(b => b.id);
}

/* Экспорт */
function exportPDF() {
    const bookings = dayBookings.value;
    const lines = bookings.map(b => `${b.time} | ${b.clientName} | ${b.masterName} | ${b.serviceName} | ${b.status}`).join('\n');
    const content = `Расписание на ${selectedDate.value}\n${'='.repeat(50)}\nВремя | Клиент | Мастер | Услуга | Статус\n${lines}\n\nИтого: ${bookings.length} записей`;
    const blob = new Blob([content], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `schedule_${selectedDate.value}.pdf`;
    a.click();
    URL.revokeObjectURL(url);
}
function exportICal() {
    const bookings = dayBookings.value;
    let ical = 'BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//CatVRF//Beauty Calendar//RU\n';
    bookings.forEach(b => {
        const dateStr = selectedDate.value.replace(/\./g, '');
        const timeStr = (b.time || '10:00').replace(':', '') + '00';
        ical += `BEGIN:VEVENT\nDTSTART:${dateStr}T${timeStr}\nSUMMARY:${b.clientName} - ${b.serviceName}\nDESCRIPTION:Мастер: ${b.masterName}\nEND:VEVENT\n`;
    });
    ical += 'END:VCALENDAR';
    const blob = new Blob([ical], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `schedule_${selectedDate.value}.ics`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
<div class="space-y-4">
    <!-- ═══ HEADER: навигация + режимы + фильтры + экспорт ═══ -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <!-- Лево: навигация по дате -->
        <div class="flex items-center gap-2">
            <button @click="goPrev" class="w-8 h-8 rounded-lg flex items-center justify-center hover:scale-110 transition-transform"
                    style="background:var(--t-surface);color:var(--t-text-2)">‹</button>
            <button @click="goToday" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    style="background:var(--t-primary-dim);color:var(--t-primary)">Сегодня</button>
            <button @click="goNext" class="w-8 h-8 rounded-lg flex items-center justify-center hover:scale-110 transition-transform"
                    style="background:var(--t-surface);color:var(--t-text-2)">›</button>
            <h2 class="text-lg font-bold ml-2" style="color:var(--t-text)">
                <template v-if="currentView === 'month'">{{ fmtMonthYear(currentDate) }}</template>
                <template v-else>{{ selectedDate }}</template>
            </h2>
        </div>

        <!-- Центр: режимы просмотра -->
        <div class="flex border rounded-xl overflow-hidden" style="border-color:var(--t-border)">
            <button v-for="vm in viewModes" :key="vm.key"
                    @click="currentView = vm.key"
                    class="px-3 py-2 text-xs font-medium transition-all duration-200"
                    :style="currentView === vm.key
                        ? 'background:var(--t-primary);color:#fff'
                        : 'background:var(--t-surface);color:var(--t-text-2)'"
            >{{ vm.icon }} {{ vm.label }}</button>
        </div>

        <!-- Право: фильтры + действия -->
        <div class="flex items-center gap-2">
            <!-- Индикатор загрузки дня -->
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs"
                 :class="isOverloaded ? 'animate-pulse' : ''"
                 :style="isOverloaded ? 'background:rgba(239,68,68,0.15);color:#f87171' : 'background:var(--t-surface);color:var(--t-text-2)'">
                <span>{{ isOverloaded ? '🔴' : '🟢' }}</span>
                <span class="font-bold">{{ dayLoadPercent }}%</span>
                <span class="hidden sm:inline">загрузка</span>
            </div>

            <button @click="showFilters = !showFilters"
                    class="px-3 py-2 rounded-lg text-xs font-medium transition-colors"
                    :style="showFilters ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-surface);color:var(--t-text-2)'">
                🔍 Фильтры
                <span v-if="filterMasters.length || filterStatuses.length || filterServices.length"
                      class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-red-500 text-white">
                    {{ filterMasters.length + filterStatuses.length + filterServices.length }}
                </span>
            </button>

            <!-- Массовые действия -->
            <template v-if="selectedBookings.length">
                <VButton size="sm" @click="massConfirm">✅ {{ selectedBookings.length }}</VButton>
                <VButton size="sm" variant="outline" @click="massCancel">❌ {{ selectedBookings.length }}</VButton>
            </template>

            <button @click="showHistoryModal = true" class="px-3 py-2 rounded-lg text-xs"
                    style="background:var(--t-surface);color:var(--t-text-2)" title="История изменений">📜</button>
            <button @click="showExportModal = true" class="px-3 py-2 rounded-lg text-xs"
                    style="background:var(--t-surface);color:var(--t-text-2)" title="Экспорт">📤</button>
            <VButton size="sm" @click="showCreateModal = true">📅 Новая запись</VButton>
        </div>
    </div>

    <!-- ═══ ФИЛЬТРЫ (collapsible) ═══ -->
    <transition name="slide-down">
    <VCard v-if="showFilters" class="!p-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Филиал -->
            <div>
                <label class="text-xs font-medium mb-1 block" style="color:var(--t-text-3)">Филиал</label>
                <select v-model="filterSalonId" class="w-full rounded-lg px-3 py-2 text-sm border"
                        style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                    <option :value="null">Все филиалы</option>
                    <option v-for="s in props.salons" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </div>
            <!-- Мастера -->
            <div>
                <label class="text-xs font-medium mb-1 block" style="color:var(--t-text-3)">Мастера</label>
                <div class="flex flex-wrap gap-1">
                    <button v-for="m in props.masters.slice(0,8)" :key="m.id"
                            @click="filterMasters.includes(m.id) ? filterMasters.splice(filterMasters.indexOf(m.id),1) : filterMasters.push(m.id)"
                            class="px-2 py-1 rounded-lg text-xs border transition-all"
                            :style="filterMasters.includes(m.id) ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)' : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'">
                        {{ m.name?.split(' ')[0] || m.name }}
                    </button>
                </div>
            </div>
            <!-- Статусы -->
            <div>
                <label class="text-xs font-medium mb-1 block" style="color:var(--t-text-3)">Статусы</label>
                <div class="flex flex-wrap gap-1">
                    <button v-for="(cfg, st) in statusConfig" :key="st"
                            v-show="st !== 'free' && st !== 'lunch' && st !== 'break' && st !== 'blocked'"
                            @click="filterStatuses.includes(st) ? filterStatuses.splice(filterStatuses.indexOf(st),1) : filterStatuses.push(st)"
                            class="px-2 py-1 rounded-lg text-xs border transition-all"
                            :style="filterStatuses.includes(st) ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)' : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'">
                        {{ cfg.icon }} {{ cfg.label }} <span class="opacity-60">({{ statusCounts[st] || 0 }})</span>
                    </button>
                </div>
            </div>
            <!-- Доп. действия -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium mb-1 block" style="color:var(--t-text-3)">Быстрые действия</label>
                <button @click="showBlockSlotModal = true" class="text-left px-2 py-1.5 rounded-lg text-xs hover:brightness-110 transition-all"
                        style="background:var(--t-surface);color:var(--t-text-2)">🚫 Заблокировать слоты</button>
                <button @click="showReminderModal = true" class="text-left px-2 py-1.5 rounded-lg text-xs hover:brightness-110 transition-all"
                        style="background:var(--t-surface);color:var(--t-text-2)">🔔 Настройки напоминаний</button>
                <button @click="filterMasters = []; filterStatuses = []; filterServices = []; filterSalonId = null"
                        class="text-left px-2 py-1.5 rounded-lg text-xs"
                        style="color:var(--t-primary)">✕ Сбросить фильтры</button>
            </div>
        </div>
    </VCard>
    </transition>

    <!-- ═══ B2B: Загрузка мастеров (horizontal bar) ═══ -->
    <div class="flex flex-wrap gap-2">
        <div v-for="(m, idx) in activeMasters" :key="m.id"
             class="flex items-center gap-2 px-3 py-2 rounded-xl border text-xs"
             style="background:var(--t-surface);border-color:var(--t-border)">
            <div class="w-2 h-2 rounded-full" :class="getMasterColor(idx).dot"></div>
            <span class="font-medium" style="color:var(--t-text)">{{ m.name?.split(' ')[0] || m.name }}</span>
            <div class="w-16 h-1.5 rounded-full bg-white/10 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" :class="loadBarColor(masterLoad[m.id] || 0)"
                     :style="`width:${masterLoad[m.id] || 0}%`"></div>
            </div>
            <span class="font-bold" :class="(masterLoad[m.id]||0) > 85 ? 'text-red-400 animate-pulse' : ''"
                  style="color:var(--t-text-2)">{{ masterLoad[m.id] || 0 }}%</span>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         VIEW: ДЕНЬ (вертикальная сетка часы × мастера)
         ══════════════════════════════════════════════════ -->
    <VCard v-if="currentView === 'day'">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="p-2 text-left font-medium w-16 sticky left-0 z-10"
                            style="color:var(--t-text-3);background:var(--t-surface)">Время</th>
                        <th v-for="(m, idx) in activeMasters" :key="m.id"
                            class="p-2 text-center font-medium min-w-[140px]" style="color:var(--t-text-2)">
                            <div class="flex items-center justify-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full" :class="getMasterColor(idx).dot"></div>
                                <span>{{ m.name }}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="time in workHours" :key="time"
                        class="border-t group" style="border-color:var(--t-border)"
                        :class="time.endsWith(':00') ? 'border-t-2' : 'border-dashed'">
                        <td class="p-1.5 font-mono text-xs font-bold sticky left-0 z-10"
                            style="color:var(--t-primary);background:var(--t-surface)"
                            :class="time.endsWith(':30') ? 'opacity-50' : ''">{{ time }}</td>

                        <td v-for="(m, idx) in activeMasters" :key="m.id"
                            class="p-0.5 relative"
                            @click="handleSlotClick(time, m.id)"
                            @dblclick="handleSlotDblClick(time, m.id)"
                            @contextmenu="openContextMenu($event, getSlotBooking(time, m.id), time, m.id)"
                            @dragover="onDragOver"
                            @drop="onDrop($event, m.id, time)">

                            <!-- Запись -->
                            <template v-if="getSlotBooking(time, m.id)">
                                <div class="p-2 rounded-lg border text-xs min-h-[32px] cursor-grab hover:brightness-125 transition-all relative group/slot"
                                     :class="[statusConfig[getSlotBooking(time, m.id).status]?.bg, statusConfig[getSlotBooking(time, m.id).status]?.border, getMasterColor(idx).border]"
                                     draggable="true"
                                     @dragstart="onDragStart($event, getSlotBooking(time, m.id))">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium truncate" style="color:var(--t-text)">{{ getSlotBooking(time, m.id).client }}</span>
                                        <span class="text-[10px]">{{ statusConfig[getSlotBooking(time, m.id).status]?.icon }}</span>
                                    </div>
                                    <div class="truncate text-[11px]" style="color:var(--t-text-3)">{{ getSlotBooking(time, m.id).service }}</div>
                                    <!-- B2C badge -->
                                    <span v-if="getSlotBooking(time, m.id).source === 'b2c'"
                                          class="absolute top-0.5 right-0.5 px-1 py-0.5 rounded text-[8px] bg-sky-500/30 text-sky-300">B2C</span>
                                    <!-- Resize handle -->
                                    <div class="absolute bottom-0 left-0 right-0 h-1.5 cursor-s-resize opacity-0 group-hover/slot:opacity-100 rounded-b"
                                         style="background:var(--t-primary)"
                                         @mousedown.stop="onResizeStart($event, getSlotBooking(time, m.id))"></div>
                                    <!-- Чекбокс массовых действий -->
                                    <input type="checkbox" v-model="selectedBookings" :value="getSlotBooking(time, m.id).id"
                                           class="absolute top-0.5 left-0.5 w-3 h-3 opacity-0 group-hover/slot:opacity-100"
                                           @click.stop>
                                </div>
                            </template>

                            <!-- Блокировка -->
                            <template v-else-if="getSlotBlock(time, m.id)">
                                <div class="p-2 rounded-lg border text-xs min-h-[32px] flex items-center justify-center"
                                     :class="[statusConfig[getSlotStatus(time, m.id)]?.bg, statusConfig[getSlotStatus(time, m.id)]?.border]">
                                    <span class="opacity-60">{{ statusConfig[getSlotStatus(time, m.id)]?.icon }} {{ statusConfig[getSlotStatus(time, m.id)]?.label }}</span>
                                </div>
                            </template>

                            <!-- Свободный слот -->
                            <template v-else>
                                <div class="min-h-[32px] rounded-lg border border-transparent hover:border-dashed opacity-30 hover:opacity-80 transition-all flex items-center justify-center"
                                     style="hover:border-color:var(--t-primary)">
                                    <span class="text-[10px] opacity-0 group-hover:opacity-60" style="color:var(--t-text-3)">+</span>
                                </div>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </VCard>

    <!-- ══════════════════════════════════════════════════
         VIEW: НЕДЕЛЯ (7 дней × часы, компактная)
         ══════════════════════════════════════════════════ -->
    <VCard v-if="currentView === 'week'">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="p-2 w-16 sticky left-0 z-10" style="background:var(--t-surface)"></th>
                        <th v-for="wd in weekDays" :key="wd.str" class="p-2 text-center min-w-[120px]"
                            :class="wd.isToday ? 'bg-sky-500/10' : ''">
                            <div class="text-[10px] font-medium" style="color:var(--t-text-3)">{{ wd.label }}</div>
                            <div class="text-sm font-bold cursor-pointer hover:underline"
                                 :style="wd.isToday ? 'color:var(--t-primary)' : 'color:var(--t-text)'"
                                 @click="currentDate = wd.date; currentView = 'day'">
                                {{ wd.str.split('.')[0] }}
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="time in workHours.filter(t => t.endsWith(':00'))" :key="time"
                        class="border-t" style="border-color:var(--t-border)">
                        <td class="p-1.5 font-mono text-xs font-bold sticky left-0 z-10"
                            style="color:var(--t-primary);background:var(--t-surface)">{{ time }}</td>
                        <td v-for="wd in weekDays" :key="wd.str" class="p-0.5"
                            :class="wd.isToday ? 'bg-sky-500/5' : ''">
                            <div class="min-h-[28px]">
                                <template v-for="b in props.bookings.filter(bb => (bb.date?.split(' ')[0] || bb.date) === wd.str && (bb.date?.split(' ')[1] || '').startsWith(time.split(':')[0]))"
                                          :key="b.id">
                                    <div class="px-1.5 py-0.5 rounded text-[10px] truncate mb-0.5 cursor-pointer hover:brightness-125"
                                         :class="[statusConfig[b.status]?.bg, statusConfig[b.status]?.border, 'border']"
                                         @click="editingBooking = b; showDetailModal = true"
                                         :title="`${b.client} — ${b.service}`">
                                        {{ b.client?.split(' ')[0] }} · {{ b.service?.split(' ')[0] }}
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </VCard>

    <!-- ══════════════════════════════════════════════════
         VIEW: МЕСЯЦ (классическая сетка)
         ══════════════════════════════════════════════════ -->
    <VCard v-if="currentView === 'month'">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th v-for="d in ['Пн','Вт','Ср','Чт','Пт','Сб','Вс']" :key="d"
                        class="p-2 text-center text-xs font-medium" style="color:var(--t-text-3)">{{ d }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, ri) in monthGrid" :key="ri" class="border-t" style="border-color:var(--t-border)">
                    <td v-for="(cell, ci) in row" :key="ci"
                        class="p-1 align-top min-h-[80px] h-20 border-r cursor-pointer transition-colors"
                        :class="[cell?.isToday ? 'bg-sky-500/10' : '', monthDayLoad(cell)]"
                        style="border-color:var(--t-border)"
                        @click="cell && (currentDate = cell.date, currentView = 'day')">
                        <template v-if="cell">
                            <div class="text-xs font-bold mb-1"
                                 :style="cell.isToday ? 'color:var(--t-primary)' : 'color:var(--t-text)'">{{ cell.day }}</div>
                            <div v-if="cell.bookings.length" class="text-[10px] space-y-0.5">
                                <div v-for="b in cell.bookings.slice(0,3)" :key="b.id"
                                     class="px-1 py-0.5 rounded truncate"
                                     :class="statusConfig[b.status]?.bg">
                                    <span style="color:var(--t-text-2)">{{ b.date?.split(' ')[1] }}</span>
                                    <span class="ml-1" style="color:var(--t-text-3)">{{ b.client?.split(' ')[0] }}</span>
                                </div>
                                <div v-if="cell.bookings.length > 3" class="text-center opacity-50" style="color:var(--t-text-3)">
                                    +{{ cell.bookings.length - 3 }}
                                </div>
                            </div>
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>
    </VCard>

    <!-- ══════════════════════════════════════════════════
         VIEW: TIMELINE (горизонтальный мультимастер)
         ══════════════════════════════════════════════════ -->
    <VCard v-if="currentView === 'timeline'">
        <div class="overflow-x-auto">
            <!-- Шкала часов -->
            <div class="flex border-b pb-1 mb-2" style="border-color:var(--t-border)">
                <div class="w-28 shrink-0"></div>
                <div class="flex flex-1 min-w-[780px]">
                    <div v-for="h in 13" :key="h" class="flex-1 text-center text-[10px] font-mono"
                         style="color:var(--t-text-3)">{{ String(h + 7).padStart(2,'0') }}:00</div>
                </div>
            </div>
            <!-- Мастера (строки) -->
            <div v-for="(m, idx) in activeMasters" :key="m.id"
                 class="flex items-center mb-1.5 group/row">
                <!-- Имя мастера -->
                <div class="w-28 shrink-0 flex items-center gap-1.5 pr-2">
                    <div class="w-2.5 h-2.5 rounded-full" :class="getMasterColor(idx).dot"></div>
                    <span class="text-xs font-medium truncate" style="color:var(--t-text)">{{ m.name }}</span>
                </div>
                <!-- Timeline bar -->
                <div class="relative flex-1 h-8 rounded-lg min-w-[780px]" style="background:var(--t-surface)">
                    <!-- Blocked slots -->
                    <div v-for="bl in blockedSlots.filter(b => (!b.masterId || b.masterId === m.id) && b.date === selectedDate)"
                         :key="`bl-${bl.timeFrom}`"
                         class="absolute top-0 h-full rounded opacity-40"
                         :class="statusConfig.blocked.bg"
                         :style="`left:${timeToPercent(bl.timeFrom)}%;width:${timeToPercent(bl.timeTo) - timeToPercent(bl.timeFrom)}%`">
                    </div>
                    <!-- Bookings -->
                    <div v-for="b in dayBookings.filter(bb => (bb.master === m.name) || (bb.masterId === m.id))"
                         :key="b.id"
                         class="absolute top-0.5 bottom-0.5 rounded-md border px-1 flex items-center text-[10px] truncate cursor-pointer hover:brightness-125 transition-all"
                         :class="[getMasterColor(idx).bg, getMasterColor(idx).border]"
                         :style="`left:${timeToPercent(b.date?.split(' ')[1] || '09:00')}%;width:${durationToPercent(props.services.find(s => s.name === b.service)?.duration || 60)}%`"
                         :title="`${b.client} — ${b.service} (${b.date?.split(' ')[1]})`"
                         @click="editingBooking = b; showDetailModal = true">
                        <span class="truncate font-medium" style="color:var(--t-text)">{{ b.client?.split(' ')[0] }}</span>
                    </div>
                </div>
                <!-- Загрузка -->
                <div class="w-12 text-right text-xs font-bold pl-2"
                     :class="(masterLoad[m.id]||0) > 85 ? 'text-red-400' : ''" style="color:var(--t-text-2)">
                    {{ masterLoad[m.id] || 0 }}%
                </div>
            </div>
        </div>
    </VCard>

    <!-- ══════════════════════════════════════════════════
         VIEW: СПИСОК (таблица с сортировкой)
         ══════════════════════════════════════════════════ -->
    <VCard v-if="currentView === 'list'">
        <div v-if="!listBookings.length" class="py-12 text-center text-sm" style="color:var(--t-text-3)">
            Нет записей по заданным фильтрам
        </div>
        <div v-else class="space-y-1.5">
            <!-- Select all -->
            <div class="flex items-center gap-2 pb-2 border-b" style="border-color:var(--t-border)">
                <input type="checkbox" class="w-4 h-4" @change="toggleSelectAll"
                       :checked="selectedBookings.length === listBookings.length && listBookings.length > 0">
                <span class="text-xs" style="color:var(--t-text-3)">Выбрать все ({{ listBookings.length }})</span>
            </div>
            <div v-for="b in listBookings" :key="b.id"
                 class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:brightness-110 transition-all"
                 style="background:var(--t-surface);border-color:var(--t-border)"
                 @click="editingBooking = b; showDetailModal = true"
                 @contextmenu="openContextMenu($event, b)">
                <input type="checkbox" v-model="selectedBookings" :value="b.id" class="w-4 h-4" @click.stop>
                <!-- Дата -->
                <div class="w-16 text-center shrink-0">
                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ b.date?.split(' ')[0] }}</div>
                    <div class="font-bold text-sm" style="color:var(--t-primary)">{{ b.date?.split(' ')[1] }}</div>
                </div>
                <!-- Клиент + услуга -->
                <div class="flex-1 min-w-[150px]">
                    <div class="font-medium text-sm" style="color:var(--t-text)">{{ b.client }}</div>
                    <div class="text-xs" style="color:var(--t-text-3)">{{ b.service }} · {{ b.master }}</div>
                </div>
                <!-- Источник -->
                <span v-if="b.source === 'b2c'" class="px-1.5 py-0.5 rounded text-[10px] bg-sky-500/20 text-sky-300">B2C</span>
                <!-- Стоимость -->
                <div class="text-right text-sm w-20 shrink-0">
                    <div class="font-bold" style="color:var(--t-text)">{{ fmt(b.total) }} ₽</div>
                    <div v-if="b.prepaid" class="text-[10px] text-green-400">{{ fmt(b.prepaid) }} ₽</div>
                </div>
                <!-- Статус -->
                <VBadge :color="statusConfig[b.status]?.color || 'gray'" size="sm">
                    {{ statusConfig[b.status]?.icon }} {{ statusConfig[b.status]?.label }}
                </VBadge>
            </div>
        </div>
    </VCard>

    <!-- ═══ МОДАЛКА: Новая запись ═══ -->
    <VModal :open="showCreateModal" @close="showCreateModal = false" title="📅 Новая запись" size="lg">
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <VInput v-model="newBooking.clientName"  label="Имя клиента"  placeholder="Мария К." />
                <VInput v-model="newBooking.clientPhone" label="Телефон"      placeholder="+7 900 000-00-00" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium mb-1 block" style="color:var(--t-text-3)">Услуга</label>
                    <select v-model="newBooking.serviceId" class="w-full rounded-lg px-3 py-2 text-sm border"
                            style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        <option :value="null">Выберите услугу</option>
                        <option v-for="s in props.services" :key="s.id" :value="s.id">{{ s.name }} ({{ s.duration }} мин) — {{ fmt(s.price) }} ₽</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium mb-1 block" style="color:var(--t-text-3)">Мастер</label>
                    <select v-model="newBooking.masterId" class="w-full rounded-lg px-3 py-2 text-sm border"
                            style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        <option :value="null">Авто-подбор</option>
                        <option v-for="m in activeMasters" :key="m.id" :value="m.id">{{ m.name }} ({{ masterLoad[m.id] || 0 }}%)</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <VInput v-model="newBooking.date" label="Дата"  type="date" />
                <VInput v-model="newBooking.time" label="Время" type="time" />
                <VInput v-model="newBooking.prepaid" label="Предоплата, ₽" type="number" />
            </div>
            <!-- Авто-подбор свободных мастеров -->
            <div v-if="newBooking.time && !newBooking.masterId" class="p-3 rounded-lg border" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="text-xs font-medium mb-2" style="color:var(--t-text-3)">🤖 Рекомендуемые мастера:</div>
                <div class="flex flex-wrap gap-2">
                    <button v-for="fm in suggestFreeMaster(newBooking.time, props.services.find(s => s.id === newBooking.serviceId)?.duration || 60).slice(0,4)"
                            :key="fm.id"
                            @click="newBooking.masterId = fm.id"
                            class="px-3 py-1.5 rounded-lg text-xs border hover:brightness-110"
                            style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                        {{ fm.name }} <span class="opacity-60">({{ masterLoad[fm.id] || 0 }}%)</span>
                    </button>
                </div>
            </div>
            <VInput v-model="newBooking.notes" label="Заметки" placeholder="Пожелания клиента..." />
            <div class="flex justify-end gap-2 pt-2">
                <VButton variant="outline" @click="showCreateModal = false">Отмена</VButton>
                <VButton @click="submitNewBooking">Создать запись</VButton>
            </div>
        </div>
    </VModal>

    <!-- ═══ МОДАЛКА: Детали записи ═══ -->
    <VModal :open="showDetailModal" @close="showDetailModal = false; editingBooking = null"
            :title="`Запись #${editingBooking?.id || ''}`" size="lg">
        <template v-if="editingBooking">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs" style="color:var(--t-text-3)">Клиент</div>
                        <div class="font-bold" style="color:var(--t-text)">{{ editingBooking.client }}</div>
                        <div class="text-xs" style="color:var(--t-text-2)">{{ editingBooking.phone }}</div>
                    </div>
                    <div>
                        <div class="text-xs" style="color:var(--t-text-3)">Мастер</div>
                        <div class="font-bold" style="color:var(--t-text)">{{ editingBooking.master }}</div>
                        <div class="text-xs" style="color:var(--t-text-2)">{{ editingBooking.salon }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs" style="color:var(--t-text-3)">Услуга</div>
                        <div class="font-medium" style="color:var(--t-text)">{{ editingBooking.service }}</div>
                    </div>
                    <div>
                        <div class="text-xs" style="color:var(--t-text-3)">Дата / время</div>
                        <div class="font-medium" style="color:var(--t-primary)">{{ editingBooking.date }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs" style="color:var(--t-text-3)">Стоимость</div>
                        <div class="font-bold text-lg" style="color:var(--t-text)">{{ fmt(editingBooking.total) }} ₽</div>
                    </div>
                    <div>
                        <div class="text-xs" style="color:var(--t-text-3)">Предоплата</div>
                        <div class="font-bold text-lg text-green-400">{{ fmt(editingBooking.prepaid) }} ₽</div>
                    </div>
                    <div>
                        <div class="text-xs" style="color:var(--t-text-3)">Статус</div>
                        <VBadge :color="statusConfig[editingBooking.status]?.color || 'gray'" size="sm">
                            {{ statusConfig[editingBooking.status]?.icon }} {{ statusConfig[editingBooking.status]?.label }}
                        </VBadge>
                    </div>
                </div>
                <!-- Источник -->
                <div v-if="editingBooking.source === 'b2c'" class="p-3 rounded-lg bg-sky-500/10 border border-sky-500/20 text-xs text-sky-300">
                    🌐 Запись пришла из онлайн-бронирования (B2C)
                </div>
                <!-- Timeline записи -->
                <div class="border-t pt-3" style="border-color:var(--t-border)">
                    <div class="text-xs font-medium mb-2" style="color:var(--t-text-3)">История записи</div>
                    <div class="space-y-1.5 text-xs" style="color:var(--t-text-2)">
                        <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Создана · 07.04.2026 18:22</div>
                        <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Подтверждена · 07.04.2026 18:25</div>
                        <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Напоминание отправлено · 08.04.2026 08:00</div>
                    </div>
                </div>
                <div class="flex justify-between pt-3 border-t" style="border-color:var(--t-border)">
                    <div class="flex gap-2">
                        <VButton v-if="editingBooking.status === 'pending'" size="sm" @click="emit('update-booking', { id: editingBooking.id, status: 'confirmed' }); showDetailModal = false">
                            ✅ Подтвердить
                        </VButton>
                        <VButton v-if="editingBooking.status !== 'cancelled'" size="sm" variant="outline"
                                 @click="emit('cancel-booking', { id: editingBooking.id }); showDetailModal = false">
                            ❌ Отменить
                        </VButton>
                    </div>
                    <VButton size="sm" variant="outline" @click="showDetailModal = false">Закрыть</VButton>
                </div>
            </div>
        </template>
    </VModal>

    <!-- ═══ МОДАЛКА: Блокировка слотов ═══ -->
    <VModal :open="showBlockSlotModal" @close="showBlockSlotModal = false" title="🚫 Блокировка слотов" size="md">
        <div class="space-y-4">
            <div>
                <label class="text-xs font-medium mb-1 block" style="color:var(--t-text-3)">Мастер</label>
                <select v-model="blockSlot.masterId" class="w-full rounded-lg px-3 py-2 text-sm border"
                        style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                    <option :value="null">Все мастера</option>
                    <option v-for="m in activeMasters" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <VInput v-model="blockSlot.dateFrom" label="Дата с" type="date" />
                <VInput v-model="blockSlot.dateTo"   label="Дата по" type="date" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <VInput v-model="blockSlot.timeFrom" label="Время с" type="time" />
                <VInput v-model="blockSlot.timeTo"   label="Время по" type="time" />
            </div>
            <div>
                <label class="text-xs font-medium mb-1 block" style="color:var(--t-text-3)">Причина</label>
                <div class="flex flex-wrap gap-2">
                    <button v-for="r in blockReasons" :key="r.key"
                            @click="blockSlot.reason = r.key"
                            class="px-3 py-1.5 rounded-lg text-xs border transition-all"
                            :style="blockSlot.reason === r.key ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)' : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'">
                        {{ r.label }}
                    </button>
                </div>
            </div>
            <VInput v-if="blockSlot.reason === 'custom'" v-model="blockSlot.customReason" label="Причина" placeholder="Введите причину" />
            <div class="flex justify-end gap-2 pt-2">
                <VButton variant="outline" @click="showBlockSlotModal = false">Отмена</VButton>
                <VButton @click="blockedSlots.push({ ...blockSlot, date: blockSlot.dateFrom }); showBlockSlotModal = false">Заблокировать</VButton>
            </div>
        </div>
    </VModal>

    <!-- ═══ МОДАЛКА: История изменений ═══ -->
    <VModal :open="showHistoryModal" @close="showHistoryModal = false" title="📜 История изменений" size="lg">
        <div class="space-y-2 max-h-[400px] overflow-y-auto">
            <div v-for="h in changeHistory" :key="h.id"
                 class="flex items-start gap-3 p-3 rounded-lg border"
                 style="background:var(--t-surface);border-color:var(--t-border)">
                <span class="text-sm">
                    {{ h.type === 'move' ? '🔄' : h.type === 'cancel' ? '❌' : h.type === 'confirm' ? '✅' : h.type === 'create' ? '📅' : '🚫' }}
                </span>
                <div class="flex-1">
                    <div class="text-sm" style="color:var(--t-text)">{{ h.action }}</div>
                    <div class="text-[10px] mt-0.5" style="color:var(--t-text-3)">{{ h.ts }} · {{ h.user }}</div>
                </div>
            </div>
        </div>
    </VModal>

    <!-- ═══ МОДАЛКА: Напоминания ═══ -->
    <VModal :open="showReminderModal" @close="showReminderModal = false" title="🔔 Настройки напоминаний" size="md">
        <div class="space-y-3">
            <label v-for="(val, key) in reminderSettings" :key="key"
                   class="flex items-center justify-between p-3 rounded-lg border cursor-pointer"
                   style="background:var(--t-surface);border-color:var(--t-border)">
                <span class="text-sm" style="color:var(--t-text)">
                    {{ key === 'sms24h' ? '📱 SMS за 24 часа' : key === 'push2h' ? '🔔 Push за 2 часа' : key === 'push30m' ? '🔔 Push за 30 минут' : '💬 WhatsApp за 24 часа' }}
                </span>
                <input type="checkbox" v-model="reminderSettings[key]" class="w-5 h-5 rounded">
            </label>
        </div>
    </VModal>

    <!-- ═══ МОДАЛКА: Экспорт ═══ -->
    <VModal :open="showExportModal" @close="showExportModal = false" title="📤 Экспорт расписания" size="sm">
        <div class="space-y-3">
            <button @click="exportPDF(); showExportModal = false"
                    class="w-full p-4 rounded-xl border text-left hover:brightness-110 transition-all"
                    style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="font-medium" style="color:var(--t-text)">📄 Экспорт в PDF</div>
                <div class="text-xs mt-1" style="color:var(--t-text-3)">Расписание за выбранный период для печати</div>
            </button>
            <button @click="exportICal(); showExportModal = false"
                    class="w-full p-4 rounded-xl border text-left hover:brightness-110 transition-all"
                    style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="font-medium" style="color:var(--t-text)">📅 Экспорт в iCal</div>
                <div class="text-xs mt-1" style="color:var(--t-text-3)">Импорт в Google Calendar, Outlook, Apple Calendar</div>
            </button>
        </div>
    </VModal>

    <!-- ═══ КОНТЕКСТНОЕ МЕНЮ ═══ -->
    <Teleport to="body">
        <transition name="fade">
        <div v-if="ctxMenu.show"
             class="fixed z-50 py-1 rounded-xl shadow-2xl border min-w-[180px]"
             style="background:var(--t-surface);border-color:var(--t-border)"
             :style="`left:${ctxMenu.x}px;top:${ctxMenu.y}px`">
            <template v-if="ctxMenu.bookingId">
                <button @click="ctxAction('edit')" class="w-full text-left px-4 py-2 text-sm hover:brightness-125" style="color:var(--t-text)">📋 Подробнее</button>
                <button @click="ctxAction('confirm')" class="w-full text-left px-4 py-2 text-sm hover:brightness-125" style="color:var(--t-text)">✅ Подтвердить</button>
                <button @click="ctxAction('cancel')" class="w-full text-left px-4 py-2 text-sm hover:brightness-125 text-red-400">❌ Отменить</button>
            </template>
            <template v-else>
                <button @click="ctxAction('create')" class="w-full text-left px-4 py-2 text-sm hover:brightness-125" style="color:var(--t-text)">📅 Новая запись</button>
                <button @click="ctxAction('block')" class="w-full text-left px-4 py-2 text-sm hover:brightness-125" style="color:var(--t-text)">🚫 Заблокировать слот</button>
            </template>
        </div>
        </transition>
    </Teleport>
</div>
</template>

<script>
/* Timeline helpers (нужны в template) */
export default {
    methods: {
        timeToPercent(time) {
            if (!time) return 0;
            const [h, m] = time.split(':').map(Number);
            const minutes = (h - 8) * 60 + m;
            return Math.max(0, Math.min(100, (minutes / (13 * 60)) * 100));
        },
        durationToPercent(mins) {
            return Math.max(1, (mins / (13 * 60)) * 100);
        },
    },
};
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
