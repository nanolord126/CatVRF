<script setup>
/**
 * BeautyStaff — полный модуль управления персоналом салона красоты.
 * 6 табов: обзор, график смен, KPI, зарплаты, документы, настройки.
 * Получает массив мастеров / салонов через props.
 */
import { ref, computed, reactive } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VStatCard from '../../UI/VStatCard.vue';
import VTable from '../../UI/VTable.vue';

const props = defineProps({
    masters: { type: Array, default: () => [] },
    salons:  { type: Array, default: () => [] },
});

const emit = defineEmits([
    'open-master', 'add-master', 'edit-master', 'fire-master',
    'create-shift', 'payout', 'export-report',
]);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ═══════════════ TABS ═══════════════ */
const tabs = [
    { key: 'overview',   icon: '👥', label: 'Обзор' },
    { key: 'schedule',   icon: '📅', label: 'Смены' },
    { key: 'kpi',        icon: '📊', label: 'KPI' },
    { key: 'payroll',    icon: '💰', label: 'Зарплаты' },
    { key: 'documents',  icon: '📁', label: 'Документы' },
    { key: 'settings',   icon: '⚙️', label: 'Настройки' },
];
const activeTab = ref('overview');

/* ═══════════════════════════════════════════════════════════════ */
/*  TOAST NOTIFICATION                                            */
/* ═══════════════════════════════════════════════════════════════ */
const showToast = ref(false);
const toastMessage = ref('');
function toast(msg) {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}

/* ═══════════════════════════════════════════════════════════════ */
/*  1. OVERVIEW — STAFF TABLE                                     */
/* ═══════════════════════════════════════════════════════════════ */
const staffFilter = reactive({ search: '', salon: '', status: '', position: '', sort: 'name' });
const selectedStaff = ref([]);

const allStaff = ref([
    { id: 1,  name: 'Анна Соколова',        phone: '+7 900 123-45-67', position: 'Стилист-колорист',   level: 'Топ',     salon: 'BeautyLab Центр',    hireDate: '10.03.2022', status: 'active',   rating: 4.9, clients: 156, revenue: 485000, commission: 35, salary: 0, hoursWeek: 40, avatar: '' },
    { id: 2,  name: 'Ольга Демидова',       phone: '+7 900 234-56-78', position: 'Мастер маникюра',    level: 'Мастер',  salon: 'BeautyLab Центр',    hireDate: '15.06.2023', status: 'active',   rating: 4.8, clients: 132, revenue: 367000, commission: 35, salary: 0, hoursWeek: 40, avatar: '' },
    { id: 3,  name: 'Светлана Романова',    phone: '+7 900 345-67-89', position: 'Мастер-косметолог',  level: 'Мастер',  salon: 'BeautyLab Юг',       hireDate: '01.09.2023', status: 'active',   rating: 4.7, clients: 98,  revenue: 412000, commission: 30, salary: 0, hoursWeek: 36, avatar: '' },
    { id: 4,  name: 'Кристина Лебедева',   phone: '+7 900 456-78-90', position: 'Бровист',            level: 'Джуниор', salon: 'BeautyLab Центр',    hireDate: '20.01.2025', status: 'active',   rating: 4.5, clients: 67,  revenue: 198000, commission: 30, salary: 0, hoursWeek: 40, avatar: '' },
    { id: 5,  name: 'Евгения Ковалёва',    phone: '+7 900 567-89-01', position: 'Администратор',      level: '',        salon: 'BeautyLab Центр',    hireDate: '01.04.2024', status: 'active',   rating: 0,   clients: 0,   revenue: 0,      commission: 0,  salary: 55000, hoursWeek: 40, avatar: '' },
    { id: 6,  name: 'Дмитрий Орлов',       phone: '+7 900 678-90-12', position: 'Маркетолог',         level: '',        salon: 'BeautyLab Центр',    hireDate: '10.11.2024', status: 'active',   rating: 0,   clients: 0,   revenue: 0,      commission: 0,  salary: 75000, hoursWeek: 40, avatar: '' },
    { id: 7,  name: 'Марина Волкова',       phone: '+7 900 789-01-23', position: 'Парикмахер',         level: 'Мастер',  salon: 'BeautyLab Юг',       hireDate: '01.02.2024', status: 'vacation', rating: 4.6, clients: 89,  revenue: 312000, commission: 35, salary: 0, hoursWeek: 40, avatar: '' },
    { id: 8,  name: 'Алина Морозова',      phone: '+7 900 890-12-34', position: 'Мастер маникюра',    level: 'Джуниор', salon: 'BeautyLab Юг',       hireDate: '15.06.2025', status: 'active',   rating: 4.3, clients: 34,  revenue: 96000,  commission: 25, salary: 0, hoursWeek: 36, avatar: '' },
    { id: 9,  name: 'Татьяна Новикова',    phone: '+7 900 901-23-45', position: 'Косметолог',         level: 'Топ',     salon: 'BeautyLab Север',    hireDate: '05.05.2021', status: 'active',   rating: 4.9, clients: 201, revenue: 620000, commission: 40, salary: 0, hoursWeek: 40, avatar: '' },
    { id: 10, name: 'Виктория Ильина',     phone: '+7 900 012-34-56', position: 'Массажист',          level: 'Мастер',  salon: 'BeautyLab Центр',    hireDate: '10.08.2023', status: 'sick',     rating: 4.7, clients: 78,  revenue: 287000, commission: 35, salary: 0, hoursWeek: 32, avatar: '' },
    { id: 11, name: 'Полина Козлова',      phone: '+7 900 112-33-44', position: 'Администратор',      level: '',        salon: 'BeautyLab Юг',       hireDate: '01.03.2025', status: 'active',   rating: 0,   clients: 0,   revenue: 0,      commission: 0,  salary: 50000, hoursWeek: 40, avatar: '' },
    { id: 12, name: 'Ирина Белова',        phone: '+7 900 223-44-55', position: 'Стилист',            level: 'Мастер',  salon: 'BeautyLab Север',    hireDate: '20.09.2024', status: 'active',   rating: 4.6, clients: 56,  revenue: 245000, commission: 30, salary: 0, hoursWeek: 40, avatar: '' },
    { id: 13, name: 'Наталья Семёнова',    phone: '+7 900 334-55-66', position: 'Лешмейкер',          level: 'Мастер',  salon: 'BeautyLab Центр',    hireDate: '15.07.2024', status: 'active',   rating: 4.8, clients: 110, revenue: 352000, commission: 35, salary: 0, hoursWeek: 36, avatar: '' },
    { id: 14, name: 'Елена Кузнецова',     phone: '+7 900 445-66-77', position: 'Парикмахер',         level: 'Джуниор', salon: 'BeautyLab Север',    hireDate: '01.01.2026', status: 'active',   rating: 4.2, clients: 19,  revenue: 58000,  commission: 25, salary: 0, hoursWeek: 40, avatar: '' },
]);

const positions = computed(() => [...new Set(allStaff.value.map(s => s.position))].sort());
const statuses = { active: '🟢 Активен', vacation: '🏖️ Отпуск', sick: '🤒 Больничный', fired: '🚫 Уволен', probation: '🆕 Испытательный' };
const statusColors = { active: 'green', vacation: 'blue', sick: 'yellow', fired: 'red', probation: 'gray' };
const levelColors = { 'Джуниор': 'gray', 'Мастер': 'blue', 'Топ': 'purple' };

const filteredStaff = computed(() => {
    let list = [...allStaff.value];
    const q = staffFilter.search.toLowerCase();
    if (q) list = list.filter(s => s.name.toLowerCase().includes(q) || s.phone.includes(q) || s.position.toLowerCase().includes(q));
    if (staffFilter.salon) list = list.filter(s => s.salon === staffFilter.salon);
    if (staffFilter.status) list = list.filter(s => s.status === staffFilter.status);
    if (staffFilter.position) list = list.filter(s => s.position === staffFilter.position);
    const sortMap = {
        name: (a, b) => a.name.localeCompare(b.name),
        rating: (a, b) => b.rating - a.rating,
        revenue: (a, b) => b.revenue - a.revenue,
        clients: (a, b) => b.clients - a.clients,
        hireDate: (a, b) => a.hireDate.localeCompare(b.hireDate),
    };
    if (sortMap[staffFilter.sort]) list.sort(sortMap[staffFilter.sort]);
    return list;
});

const staffSummary = computed(() => ({
    total: allStaff.value.length,
    active: allStaff.value.filter(s => s.status === 'active').length,
    onVacation: allStaff.value.filter(s => s.status === 'vacation').length,
    onSick: allStaff.value.filter(s => s.status === 'sick').length,
    masters: allStaff.value.filter(s => s.commission > 0).length,
    admins: allStaff.value.filter(s => s.salary > 0).length,
    avgRating: (() => { const rated = allStaff.value.filter(s => s.rating > 0); return rated.length ? (rated.reduce((a, c) => a + c.rating, 0) / rated.length).toFixed(1) : '—'; })(),
    totalRevenue: allStaff.value.reduce((a, c) => a + c.revenue, 0),
}));

function toggleStaff(id) {
    const idx = selectedStaff.value.indexOf(id);
    if (idx >= 0) selectedStaff.value.splice(idx, 1);
    else selectedStaff.value.push(id);
}
function selectAllStaff() {
    if (selectedStaff.value.length === filteredStaff.value.length) {
        selectedStaff.value = [];
    } else {
        selectedStaff.value = filteredStaff.value.map(s => s.id);
    }
}

/* ═══════════════════════════════════════════════════════════════ */
/*  ADD / EDIT EMPLOYEE MODAL                                     */
/* ═══════════════════════════════════════════════════════════════ */
const showAddModal = ref(false);
const editingStaff = ref(null);
const staffForm = reactive({
    name: '', phone: '', position: '', level: '', salon: '', commission: 30, salary: 0, hoursWeek: 40,
});
function openAddModal() {
    editingStaff.value = null;
    Object.assign(staffForm, { name: '', phone: '', position: '', level: '', salon: '', commission: 30, salary: 0, hoursWeek: 40 });
    showAddModal.value = true;
}
function openEditModal(emp) {
    editingStaff.value = emp;
    Object.assign(staffForm, { name: emp.name, phone: emp.phone, position: emp.position, level: emp.level, salon: emp.salon, commission: emp.commission, salary: emp.salary, hoursWeek: emp.hoursWeek });
    showAddModal.value = true;
}
function saveEmployee() {
    if (!staffForm.name.trim()) return;
    if (editingStaff.value) {
        Object.assign(editingStaff.value, { ...staffForm });
        toast(`✅ Сотрудник «${staffForm.name}» обновлён`);
    } else {
        allStaff.value.push({
            id: Date.now(), ...staffForm, hireDate: new Date().toLocaleDateString('ru-RU'),
            status: 'probation', rating: 0, clients: 0, revenue: 0, avatar: '',
        });
        toast(`✅ Сотрудник «${staffForm.name}» добавлен`);
        emit('add-master', staffForm);
    }
    showAddModal.value = false;
}

/* ═══════════════════════════════════════════════════════════════ */
/*  STATUS CHANGE MODAL                                           */
/* ═══════════════════════════════════════════════════════════════ */
const showStatusModal = ref(false);
const statusTarget = ref(null);
const newStatus = ref('active');
function openStatusModal(emp) {
    statusTarget.value = emp;
    newStatus.value = emp.status;
    showStatusModal.value = true;
}
function applyStatus() {
    if (statusTarget.value) {
        statusTarget.value.status = newStatus.value;
        toast(`✅ Статус «${statusTarget.value.name}» → ${statuses[newStatus.value]}`);
    }
    showStatusModal.value = false;
}

/* ═══════════════════════════════════════════════════════════════ */
/*  MASS ACTIONS                                                  */
/* ═══════════════════════════════════════════════════════════════ */
const showMassDeleteModal = ref(false);
function massAction(type) {
    const count = selectedStaff.value.length;
    if (!count) return;
    if (type === 'message') {
        toast(`✉️ Рассылка ${count} сотрудникам в очереди`);
        selectedStaff.value = [];
    } else if (type === 'bonus') {
        allStaff.value.filter(s => selectedStaff.value.includes(s.id)).forEach(s => { s.revenue += 5000; });
        toast(`🎁 Бонус начислен ${count} сотрудникам`);
        selectedStaff.value = [];
    } else if (type === 'schedule') {
        toast(`📅 Смена назначена ${count} сотрудникам`);
        selectedStaff.value = [];
    } else if (type === 'fire') {
        showMassDeleteModal.value = true;
    }
}
function confirmMassFire() {
    allStaff.value.filter(s => selectedStaff.value.includes(s.id)).forEach(s => { s.status = 'fired'; });
    toast(`🚫 Уволено ${selectedStaff.value.length} сотрудников`);
    selectedStaff.value = [];
    showMassDeleteModal.value = false;
}

/* ═══════════════════════════════════════════════════════════════ */
/*  2. SCHEDULE — SHIFT MANAGEMENT                                */
/* ═══════════════════════════════════════════════════════════════ */
const scheduleWeekOffset = ref(0);
const weekDays = computed(() => {
    const days = [];
    const dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
    const now = new Date();
    const monday = new Date(now);
    monday.setDate(now.getDate() - now.getDay() + 1 + scheduleWeekOffset.value * 7);
    for (let i = 0; i < 7; i++) {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        days.push({
            date: d.getDate(),
            dayName: dayNames[d.getDay()],
            month: d.toLocaleDateString('ru-RU', { month: 'short' }),
            fullDate: d.toLocaleDateString('ru-RU'),
            isWeekend: [0, 6].includes(d.getDay()),
            isToday: d.toDateString() === now.toDateString(),
        });
    }
    return days;
});

const shifts = ref([
    { empId: 1,  empName: 'Анна Соколова',      slots: [{ day: 0, from: '10:00', to: '20:00' }, { day: 1, from: '10:00', to: '20:00' }, { day: 2, from: '10:00', to: '20:00' }, { day: 3, from: '10:00', to: '20:00' }, { day: 4, from: '10:00', to: '20:00' }] },
    { empId: 2,  empName: 'Ольга Демидова',      slots: [{ day: 0, from: '09:00', to: '18:00' }, { day: 1, from: '09:00', to: '18:00' }, { day: 2, from: '09:00', to: '18:00' }, { day: 3, from: '09:00', to: '18:00' }, { day: 4, from: '09:00', to: '18:00' }] },
    { empId: 3,  empName: 'Светлана Романова',   slots: [{ day: 0, from: '12:00', to: '21:00' }, { day: 2, from: '12:00', to: '21:00' }, { day: 4, from: '12:00', to: '21:00' }] },
    { empId: 4,  empName: 'Кристина Лебедева',  slots: [{ day: 0, from: '10:00', to: '19:00' }, { day: 1, from: '10:00', to: '19:00' }, { day: 2, from: '10:00', to: '19:00' }, { day: 3, from: '10:00', to: '19:00' }, { day: 4, from: '10:00', to: '19:00' }] },
    { empId: 5,  empName: 'Евгения Ковалёва',   slots: [{ day: 0, from: '08:00', to: '17:00' }, { day: 1, from: '08:00', to: '17:00' }, { day: 2, from: '08:00', to: '17:00' }, { day: 3, from: '08:00', to: '17:00' }, { day: 4, from: '08:00', to: '17:00' }] },
    { empId: 7,  empName: 'Марина Волкова',      slots: [] },
    { empId: 9,  empName: 'Татьяна Новикова',   slots: [{ day: 0, from: '09:00', to: '18:00' }, { day: 1, from: '09:00', to: '18:00' }, { day: 2, from: '09:00', to: '18:00' }, { day: 3, from: '09:00', to: '18:00' }, { day: 4, from: '09:00', to: '18:00' }] },
    { empId: 10, empName: 'Виктория Ильина',     slots: [] },
    { empId: 12, empName: 'Ирина Белова',        slots: [{ day: 0, from: '10:00', to: '19:00' }, { day: 1, from: '10:00', to: '19:00' }, { day: 3, from: '10:00', to: '19:00' }, { day: 4, from: '10:00', to: '19:00' }] },
    { empId: 13, empName: 'Наталья Семёнова',   slots: [{ day: 0, from: '11:00', to: '20:00' }, { day: 1, from: '11:00', to: '20:00' }, { day: 2, from: '11:00', to: '20:00' }] },
]);

function getShiftForDay(empId, dayIdx) {
    const sh = shifts.value.find(s => s.empId === empId);
    if (!sh) return null;
    return sh.slots.find(sl => sl.day === dayIdx) || null;
}

const showShiftModal = ref(false);
const shiftForm = reactive({ empId: 0, empName: '', dayIdx: 0, from: '10:00', to: '20:00' });
function openShiftModal(empId, empName, dayIdx) {
    const existing = getShiftForDay(empId, dayIdx);
    shiftForm.empId = empId;
    shiftForm.empName = empName;
    shiftForm.dayIdx = dayIdx;
    shiftForm.from = existing?.from || '10:00';
    shiftForm.to = existing?.to || '20:00';
    showShiftModal.value = true;
}
function saveShift() {
    let sh = shifts.value.find(s => s.empId === shiftForm.empId);
    if (!sh) {
        sh = { empId: shiftForm.empId, empName: shiftForm.empName, slots: [] };
        shifts.value.push(sh);
    }
    const existIdx = sh.slots.findIndex(sl => sl.day === shiftForm.dayIdx);
    if (existIdx >= 0) {
        sh.slots[existIdx] = { day: shiftForm.dayIdx, from: shiftForm.from, to: shiftForm.to };
    } else {
        sh.slots.push({ day: shiftForm.dayIdx, from: shiftForm.from, to: shiftForm.to });
    }
    toast(`✅ Смена ${shiftForm.empName}: ${weekDays.value[shiftForm.dayIdx]?.dayName} ${shiftForm.from}–${shiftForm.to}`);
    showShiftModal.value = false;
}
function removeShift() {
    const sh = shifts.value.find(s => s.empId === shiftForm.empId);
    if (sh) {
        sh.slots = sh.slots.filter(sl => sl.day !== shiftForm.dayIdx);
        toast(`🗑️ Смена ${shiftForm.empName} ${weekDays.value[shiftForm.dayIdx]?.dayName} удалена`);
    }
    showShiftModal.value = false;
}

/* ═══════════════════════════════════════════════════════════════ */
/*  3. KPI DASHBOARD                                              */
/* ═══════════════════════════════════════════════════════════════ */
const kpiPeriod = ref('month');
const kpiData = computed(() => {
    return allStaff.value
        .filter(s => s.commission > 0 && s.status !== 'fired')
        .map(s => {
            const avgCheck = s.clients > 0 ? Math.round(s.revenue / s.clients) : 0;
            const target = s.level === 'Топ' ? 600000 : s.level === 'Мастер' ? 400000 : 200000;
            const pct = target > 0 ? Math.round(s.revenue / target * 100) : 0;
            const returnRate = Math.round(60 + Math.random() * 35);
            const upsellRate = Math.round(15 + Math.random() * 40);
            const cancelRate = Math.round(2 + Math.random() * 12);
            return {
                ...s, avgCheck, target, pct: Math.min(pct, 150),
                returnRate, upsellRate, cancelRate,
                kpiScore: Math.round((pct * 0.3 + returnRate * 0.25 + s.rating * 20 * 0.2 + (100 - cancelRate) * 0.15 + upsellRate * 0.1)),
            };
        })
        .sort((a, b) => b.kpiScore - a.kpiScore);
});

const kpiTopPerformer = computed(() => kpiData.value[0] || null);
const kpiAvgScore = computed(() => {
    if (!kpiData.value.length) return 0;
    return Math.round(kpiData.value.reduce((a, c) => a + c.kpiScore, 0) / kpiData.value.length);
});

/* ═══════════════════════════════════════════════════════════════ */
/*  4. PAYROLL                                                    */
/* ═══════════════════════════════════════════════════════════════ */
const payrollPeriod = ref('Апрель 2026');
const payrollData = computed(() => {
    return allStaff.value
        .filter(s => s.status !== 'fired')
        .map(s => {
            const basePay = s.salary > 0 ? s.salary : Math.round(s.revenue * s.commission / 100);
            const bonus = s.rating >= 4.8 ? Math.round(basePay * 0.1) : s.rating >= 4.5 ? Math.round(basePay * 0.05) : 0;
            const penalty = s.status === 'sick' ? Math.round(basePay * 0.3) : 0;
            const total = basePay + bonus - penalty;
            const isPaid = Math.random() > 0.4;
            return {
                ...s, basePay, bonus, penalty, total, isPaid,
                payType: s.salary > 0 ? 'Оклад' : 'Комиссия',
            };
        });
});

const payrollTotals = computed(() => ({
    basePay: payrollData.value.reduce((a, c) => a + c.basePay, 0),
    bonus: payrollData.value.reduce((a, c) => a + c.bonus, 0),
    penalty: payrollData.value.reduce((a, c) => a + c.penalty, 0),
    total: payrollData.value.reduce((a, c) => a + c.total, 0),
    paid: payrollData.value.filter(p => p.isPaid).reduce((a, c) => a + c.total, 0),
    unpaid: payrollData.value.filter(p => !p.isPaid).reduce((a, c) => a + c.total, 0),
}));

function payEmployee(emp) {
    const pd = payrollData.value.find(p => p.id === emp.id);
    if (pd) pd.isPaid = true;
    emit('payout', { employeeId: emp.id, amount: emp.total });
    toast(`💰 Выплата ${fmtMoney(emp.total)} → ${emp.name}`);
}
function payAll() {
    payrollData.value.filter(p => !p.isPaid).forEach(p => { p.isPaid = true; });
    emit('payout', { type: 'bulk', amount: payrollTotals.value.unpaid });
    toast(`💰 Массовая выплата ${fmtMoney(payrollTotals.value.total)} — ${payrollData.value.length} сотрудников`);
}

/* ═══════════════════════════════════════════════════════════════ */
/*  5. DOCUMENTS                                                  */
/* ═══════════════════════════════════════════════════════════════ */
const documents = ref([
    { id: 1, empId: 1,  empName: 'Анна Соколова',      type: 'contract',    name: 'Трудовой договор',        date: '10.03.2022', status: 'active' },
    { id: 2, empId: 1,  empName: 'Анна Соколова',      type: 'certificate', name: 'Сертификат L\'Oréal',     date: '15.06.2024', status: 'active' },
    { id: 3, empId: 2,  empName: 'Ольга Демидова',     type: 'contract',    name: 'Трудовой договор',        date: '15.06.2023', status: 'active' },
    { id: 4, empId: 3,  empName: 'Светлана Романова',  type: 'contract',    name: 'Трудовой договор',        date: '01.09.2023', status: 'active' },
    { id: 5, empId: 3,  empName: 'Светлана Романова',  type: 'certificate', name: 'Диплом косметолога',      date: '20.05.2020', status: 'active' },
    { id: 6, empId: 5,  empName: 'Евгения Ковалёва',   type: 'contract',    name: 'Трудовой договор',        date: '01.04.2024', status: 'active' },
    { id: 7, empId: 7,  empName: 'Марина Волкова',     type: 'vacation',    name: 'Заявление на отпуск',     date: '01.04.2026', status: 'pending' },
    { id: 8, empId: 9,  empName: 'Татьяна Новикова',   type: 'contract',    name: 'Доп. соглашение (40%)',   date: '01.01.2026', status: 'active' },
    { id: 9, empId: 10, empName: 'Виктория Ильина',    type: 'sick',        name: 'Больничный лист',         date: '05.04.2026', status: 'active' },
]);
const docTypeIcons = { contract: '📄', certificate: '🏅', vacation: '🏖️', sick: '🤒', nda: '🔒', other: '📎' };
const docStatusColors = { active: 'green', pending: 'yellow', expired: 'red', archived: 'gray' };

/* ═══════════════════════════════════════════════════════════════ */
/*  6. SETTINGS                                                   */
/* ═══════════════════════════════════════════════════════════════ */
const staffSettings = reactive({
    commissionDefault: 30,
    commissionTopBonus: 5,
    workdayStart: '09:00',
    workdayEnd: '21:00',
    maxOvertimeHours: 4,
    probationDays: 90,
    minRatingWarning: 4.0,
    autoSchedule: true,
    notifyOnLate: true,
    notifyOnSick: true,
});

function saveSettings() {
    toast('✅ Настройки персонала сохранены');
}

/* ═══════════════════════════════════════════════════════════════ */
/*  EXPORT                                                        */
/* ═══════════════════════════════════════════════════════════════ */
function exportStaffReport(format) {
    const header = '\uFEFF' + 'Имя;Должность;Салон;Статус;Рейтинг;Клиенты;Выручка;Комиссия%;Оклад\n';
    const rows = allStaff.value.map(s =>
        `${s.name};${s.position};${s.salon};${s.status};${s.rating};${s.clients};${s.revenue};${s.commission};${s.salary}`
    ).join('\n');
    const mime = format === 'csv' ? 'text/csv;charset=utf-8;' : 'application/json';
    const content = format === 'csv' ? header + rows : JSON.stringify(allStaff.value, null, 2);
    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `staff_report_${Date.now()}.${format}`;
    a.click();
    URL.revokeObjectURL(url);
    toast(`📥 Отчёт по персоналу (${format.toUpperCase()}) скачан`);
    emit('export-report', { type: 'staff', format });
}

function exportPayroll() {
    const header = '\uFEFF' + 'Имя;Должность;Тип;База;Бонус;Штраф;Итого;Статус\n';
    const rows = payrollData.value.map(p =>
        `${p.name};${p.position};${p.payType};${p.basePay};${p.bonus};${p.penalty};${p.total};${p.isPaid ? 'Оплачено' : 'Ожидание'}`
    ).join('\n');
    const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `payroll_${payrollPeriod.value.replace(/\s/g, '_')}_${Date.now()}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    toast('📥 Зарплатная ведомость скачана');
}
</script>

<template>
<div class="space-y-4">
    <!-- ═══ HEADER ═══ -->
    <div class="flex justify-between items-center flex-wrap gap-3">
        <div>
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">👥 Управление персоналом</h2>
            <div class="text-xs" style="color:var(--t-text-3)">
                {{ staffSummary.total }} сотрудников · {{ staffSummary.active }} активных · ⭐ {{ staffSummary.avgRating }}
            </div>
        </div>
        <div class="flex items-center gap-2">
            <VButton size="sm" variant="outline" @click="exportStaffReport('csv')">📥 Экспорт</VButton>
            <VButton size="sm" @click="openAddModal">➕ Добавить сотрудника</VButton>
        </div>
    </div>

    <!-- ═══ STAT CARDS ═══ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <VStatCard label="Всего сотрудников" :value="String(staffSummary.total)" icon="👥" />
        <VStatCard label="Мастера" :value="String(staffSummary.masters)" icon="💇" />
        <VStatCard label="Средний рейтинг" :value="String(staffSummary.avgRating)" icon="⭐" />
        <VStatCard label="Выручка (все)" :value="fmtMoney(staffSummary.totalRevenue)" icon="💰" />
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="flex items-center gap-1 overflow-x-auto pb-1">
        <button v-for="tab in tabs" :key="tab.key"
                class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors"
                :style="activeTab === tab.key
                    ? 'background:var(--t-primary);color:#fff'
                    : 'background:var(--t-surface);color:var(--t-text-2)'"
                @click="activeTab = tab.key">
            {{ tab.icon }} {{ tab.label }}
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 1: OVERVIEW                                      -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
        <!-- Status badges -->
        <div class="flex flex-wrap gap-2">
            <button v-for="(label, key) in statuses" :key="key"
                    class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                    :style="staffFilter.status === key
                        ? 'background:var(--t-primary);color:#fff'
                        : 'background:var(--t-surface);color:var(--t-text-2)'"
                    @click="staffFilter.status = staffFilter.status === key ? '' : key">
                {{ label }} ({{ allStaff.filter(s => s.status === key).length }})
            </button>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VInput v-model="staffFilter.search" placeholder="🔍 Поиск по имени или телефону..." />
            <select v-model="staffFilter.salon" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все салоны</option>
                <option v-for="s in salons" :key="s.id || s.name" :value="s.name || s">{{ s.name || s }}</option>
            </select>
            <select v-model="staffFilter.position" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все должности</option>
                <option v-for="p in positions" :key="p" :value="p">{{ p }}</option>
            </select>
            <select v-model="staffFilter.sort" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="name">По имени</option>
                <option value="rating">По рейтингу</option>
                <option value="revenue">По выручке</option>
                <option value="clients">По клиентам</option>
                <option value="hireDate">По дате найма</option>
            </select>
        </div>

        <!-- Mass actions bar -->
        <div v-if="selectedStaff.length" class="flex items-center gap-2 p-3 rounded-lg"
             style="background:var(--t-primary-dim)">
            <span class="text-sm font-medium" style="color:var(--t-primary)">Выбрано: {{ selectedStaff.length }}</span>
            <VButton size="sm" variant="outline" @click="massAction('message')">✉️ Рассылка</VButton>
            <VButton size="sm" variant="outline" @click="massAction('bonus')">🎁 Бонус</VButton>
            <VButton size="sm" variant="outline" @click="massAction('schedule')">📅 Смена</VButton>
            <VButton size="sm" variant="outline" style="color:#ef4444" @click="massAction('fire')">🚫 Уволить</VButton>
            <VButton size="sm" variant="outline" @click="selectedStaff = []">✕ Сброс</VButton>
        </div>

        <!-- Staff table -->
        <VCard>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left" style="color:var(--t-text-3)">
                            <th class="p-2 w-8">
                                <input type="checkbox" :checked="selectedStaff.length === filteredStaff.length && filteredStaff.length > 0"
                                       @change="selectAllStaff" class="w-4 h-4 rounded" style="accent-color:var(--t-primary)">
                            </th>
                            <th class="p-2">Сотрудник</th>
                            <th class="p-2">Должность</th>
                            <th class="p-2">Салон</th>
                            <th class="p-2 text-center">Статус</th>
                            <th class="p-2 text-center">⭐</th>
                            <th class="p-2 text-right">Клиенты</th>
                            <th class="p-2 text-right">Выручка</th>
                            <th class="p-2 text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in filteredStaff" :key="s.id"
                            class="border-t transition-colors hover:opacity-90 cursor-pointer"
                            style="border-color:var(--t-border)"
                            @click="emit('open-master', s)">
                            <td class="p-2" @click.stop>
                                <input type="checkbox" :checked="selectedStaff.includes(s.id)"
                                       @change="toggleStaff(s.id)" class="w-4 h-4 rounded" style="accent-color:var(--t-primary)">
                            </td>
                            <td class="p-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                         style="background:var(--t-primary-dim);color:var(--t-primary)">
                                        {{ s.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-medium" style="color:var(--t-text)">{{ s.name }}</div>
                                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ s.phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-2">
                                <div style="color:var(--t-text-2)">{{ s.position }}</div>
                                <VBadge v-if="s.level" :color="levelColors[s.level] || 'gray'" size="sm">{{ s.level }}</VBadge>
                            </td>
                            <td class="p-2 text-xs" style="color:var(--t-text-2)">{{ s.salon }}</td>
                            <td class="p-2 text-center" @click.stop>
                                <button @click="openStatusModal(s)">
                                    <VBadge :color="statusColors[s.status]" size="sm">{{ statuses[s.status]?.split(' ').slice(1).join(' ') || s.status }}</VBadge>
                                </button>
                            </td>
                            <td class="p-2 text-center font-bold" :style="`color:${s.rating >= 4.7 ? '#22c55e' : s.rating >= 4.4 ? 'var(--t-primary)' : '#f59e0b'}`">
                                {{ s.rating > 0 ? s.rating : '—' }}
                            </td>
                            <td class="p-2 text-right" style="color:var(--t-text)">{{ s.clients > 0 ? fmt(s.clients) : '—' }}</td>
                            <td class="p-2 text-right font-medium" style="color:var(--t-primary)">{{ s.revenue > 0 ? fmtMoney(s.revenue) : s.salary > 0 ? fmtMoney(s.salary) + ' (оклад)' : '—' }}</td>
                            <td class="p-2 text-center" @click.stop>
                                <div class="flex items-center justify-center gap-1">
                                    <VButton size="sm" variant="outline" @click="openEditModal(s)">✏️</VButton>
                                    <VButton size="sm" variant="outline" @click="emit('open-master', s)">📋</VButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-xs pt-3 border-t" style="color:var(--t-text-3);border-color:var(--t-border)">
                Показано {{ filteredStaff.length }} из {{ allStaff.length }} сотрудников
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 2: SCHEDULE                                      -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'schedule'" class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <VButton size="sm" variant="outline" @click="scheduleWeekOffset--">← Неделя</VButton>
                <VButton size="sm" variant="outline" @click="scheduleWeekOffset = 0">Текущая</VButton>
                <VButton size="sm" variant="outline" @click="scheduleWeekOffset++">Неделя →</VButton>
            </div>
            <span class="text-sm font-medium" style="color:var(--t-text)">
                {{ weekDays[0]?.fullDate }} — {{ weekDays[6]?.fullDate }}
            </span>
        </div>

        <VCard>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr style="color:var(--t-text-3)">
                            <th class="p-2 text-left min-w-[150px]">Сотрудник</th>
                            <th v-for="(d, idx) in weekDays" :key="idx" class="p-2 text-center min-w-[90px]"
                                :style="d.isToday ? 'background:var(--t-primary-dim);border-radius:8px' : ''">
                                <div class="font-bold" :style="d.isWeekend ? 'color:#ef4444' : 'color:var(--t-text)'">{{ d.dayName }}</div>
                                <div>{{ d.date }} {{ d.month }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in shifts" :key="s.empId"
                            class="border-t" style="border-color:var(--t-border)">
                            <td class="p-2 font-medium" style="color:var(--t-text)">{{ s.empName }}</td>
                            <td v-for="(d, idx) in weekDays" :key="idx" class="p-1 text-center">
                                <button v-if="getShiftForDay(s.empId, idx)"
                                        class="w-full px-1 py-1.5 rounded-lg text-[10px] font-medium transition hover:opacity-80"
                                        style="background:var(--t-primary-dim);color:var(--t-primary)"
                                        @click="openShiftModal(s.empId, s.empName, idx)">
                                    {{ getShiftForDay(s.empId, idx).from }}–{{ getShiftForDay(s.empId, idx).to }}
                                </button>
                                <button v-else
                                        class="w-full px-1 py-1.5 rounded-lg text-[10px] transition opacity-40 hover:opacity-80"
                                        style="background:var(--t-surface);color:var(--t-text-3)"
                                        @click="openShiftModal(s.empId, s.empName, idx)">
                                    {{ d.isWeekend ? '—' : '+ смена' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 3: KPI                                           -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'kpi'" class="space-y-4">
        <!-- KPI summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard label="Средний KPI" :value="String(kpiAvgScore)" icon="📊" />
            <VStatCard v-if="kpiTopPerformer" label="Лидер" :value="kpiTopPerformer.name" icon="🏆" />
            <VStatCard label="Мастеров в рейтинге" :value="String(kpiData.length)" icon="💇" />
            <VStatCard label="Выше плана" :value="String(kpiData.filter(k => k.pct >= 100).length)" icon="🎯" />
        </div>

        <!-- KPI table -->
        <VCard title="📊 Рейтинг KPI мастеров">
            <div class="space-y-3">
                <div v-for="(k, idx) in kpiData" :key="k.id"
                     class="p-4 rounded-xl border transition-all cursor-pointer hover:shadow"
                     style="background:var(--t-bg);border-color:var(--t-border)"
                     @click="emit('open-master', k)">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                             :style="`background:${idx < 3 ? 'var(--t-primary)' : 'var(--t-primary-dim)'};color:${idx < 3 ? '#fff' : 'var(--t-primary)'}`">
                            {{ idx + 1 }}
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold" style="color:var(--t-text)">{{ k.name }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">{{ k.position }} · {{ k.salon }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold" :style="`color:${k.kpiScore >= 80 ? '#22c55e' : k.kpiScore >= 50 ? 'var(--t-primary)' : '#ef4444'}`">
                                {{ k.kpiScore }}
                            </div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">KPI Score</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-5 gap-2 text-center text-[10px]">
                        <div class="p-1.5 rounded-lg" style="background:var(--t-surface)">
                            <div class="font-bold" style="color:var(--t-primary)">{{ fmtMoney(k.revenue) }}</div>
                            <div style="color:var(--t-text-3)">Выручка</div>
                        </div>
                        <div class="p-1.5 rounded-lg" style="background:var(--t-surface)">
                            <div class="font-bold" :style="`color:${k.pct >= 100 ? '#22c55e' : '#f59e0b'}`">{{ k.pct }}%</div>
                            <div style="color:var(--t-text-3)">Выполнение</div>
                        </div>
                        <div class="p-1.5 rounded-lg" style="background:var(--t-surface)">
                            <div class="font-bold" style="color:var(--t-text)">{{ k.returnRate }}%</div>
                            <div style="color:var(--t-text-3)">Возврат</div>
                        </div>
                        <div class="p-1.5 rounded-lg" style="background:var(--t-surface)">
                            <div class="font-bold" style="color:var(--t-text)">⭐ {{ k.rating }}</div>
                            <div style="color:var(--t-text-3)">Рейтинг</div>
                        </div>
                        <div class="p-1.5 rounded-lg" style="background:var(--t-surface)">
                            <div class="font-bold" :style="`color:${k.cancelRate <= 5 ? '#22c55e' : '#ef4444'}`">{{ k.cancelRate }}%</div>
                            <div style="color:var(--t-text-3)">Отмены</div>
                        </div>
                    </div>
                    <!-- Progress bar -->
                    <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:var(--t-surface)">
                        <div class="h-full rounded-full transition-all"
                             :style="`width:${Math.min(k.pct, 100)}%;background:${k.pct >= 100 ? '#22c55e' : k.pct >= 70 ? 'var(--t-primary)' : '#f59e0b'}`"></div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 4: PAYROLL                                       -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'payroll'" class="space-y-4">
        <!-- Payroll summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <VStatCard label="К выплате (всего)" :value="fmtMoney(payrollTotals.total)" icon="💰" />
            <VStatCard label="Выплачено" :value="fmtMoney(payrollTotals.paid)" icon="✅" />
            <VStatCard label="Ожидание" :value="fmtMoney(payrollTotals.unpaid)" icon="⏳" />
            <VStatCard label="Бонусы" :value="fmtMoney(payrollTotals.bonus)" icon="🎁" />
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium" style="color:var(--t-text)">Период:</span>
                <select v-model="payrollPeriod" class="px-3 py-1.5 rounded-lg text-sm border"
                        style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                    <option>Апрель 2026</option>
                    <option>Март 2026</option>
                    <option>Февраль 2026</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <VButton size="sm" variant="outline" @click="exportPayroll">📥 Скачать ведомость</VButton>
                <VButton size="sm" @click="payAll">💰 Выплатить всем</VButton>
            </div>
        </div>

        <!-- Payroll table -->
        <VCard>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left" style="color:var(--t-text-3)">
                            <th class="p-2">Сотрудник</th>
                            <th class="p-2">Тип</th>
                            <th class="p-2 text-right">База</th>
                            <th class="p-2 text-right">Бонус</th>
                            <th class="p-2 text-right">Штраф</th>
                            <th class="p-2 text-right font-bold">Итого</th>
                            <th class="p-2 text-center">Статус</th>
                            <th class="p-2 text-center">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in payrollData" :key="p.id"
                            class="border-t" style="border-color:var(--t-border)">
                            <td class="p-2">
                                <div class="font-medium" style="color:var(--t-text)">{{ p.name }}</div>
                                <div class="text-[10px]" style="color:var(--t-text-3)">{{ p.position }}</div>
                            </td>
                            <td class="p-2">
                                <VBadge :color="p.payType === 'Оклад' ? 'blue' : 'purple'" size="sm">{{ p.payType }}</VBadge>
                            </td>
                            <td class="p-2 text-right" style="color:var(--t-text)">{{ fmtMoney(p.basePay) }}</td>
                            <td class="p-2 text-right" style="color:#22c55e">{{ p.bonus > 0 ? '+' + fmtMoney(p.bonus) : '—' }}</td>
                            <td class="p-2 text-right" style="color:#ef4444">{{ p.penalty > 0 ? '-' + fmtMoney(p.penalty) : '—' }}</td>
                            <td class="p-2 text-right font-bold" style="color:var(--t-primary)">{{ fmtMoney(p.total) }}</td>
                            <td class="p-2 text-center">
                                <VBadge :color="p.isPaid ? 'green' : 'yellow'" size="sm">{{ p.isPaid ? '✅ Оплачено' : '⏳ Ожидание' }}</VBadge>
                            </td>
                            <td class="p-2 text-center">
                                <VButton v-if="!p.isPaid" size="sm" @click="payEmployee(p)">💸 Выплатить</VButton>
                                <span v-else class="text-[10px]" style="color:var(--t-text-3)">—</span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t font-bold" style="border-color:var(--t-border)">
                            <td class="p-2" style="color:var(--t-text)" colspan="2">Итого</td>
                            <td class="p-2 text-right" style="color:var(--t-text)">{{ fmtMoney(payrollTotals.basePay) }}</td>
                            <td class="p-2 text-right" style="color:#22c55e">+{{ fmtMoney(payrollTotals.bonus) }}</td>
                            <td class="p-2 text-right" style="color:#ef4444">-{{ fmtMoney(payrollTotals.penalty) }}</td>
                            <td class="p-2 text-right" style="color:var(--t-primary)">{{ fmtMoney(payrollTotals.total) }}</td>
                            <td class="p-2" colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 5: DOCUMENTS                                     -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'documents'" class="space-y-4">
        <VCard title="📁 Документы сотрудников">
            <div class="space-y-2">
                <div v-for="doc in documents" :key="doc.id"
                     class="flex items-center gap-3 p-3 rounded-lg border transition hover:shadow"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-xl">{{ docTypeIcons[doc.type] || '📎' }}</span>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ doc.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">
                            {{ doc.empName }} · {{ doc.date }}
                        </div>
                    </div>
                    <VBadge :color="docStatusColors[doc.status] || 'gray'" size="sm">{{ doc.status === 'active' ? 'Действует' : doc.status === 'pending' ? 'Ожидание' : doc.status }}</VBadge>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  TAB 6: SETTINGS                                      -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'settings'" class="space-y-4">
        <VCard title="⚙️ Настройки персонала">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Комиссия по умолчанию (%)</label>
                    <VInput v-model="staffSettings.commissionDefault" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Бонус для Топ-мастеров (%)</label>
                    <VInput v-model="staffSettings.commissionTopBonus" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Начало рабочего дня</label>
                    <VInput v-model="staffSettings.workdayStart" type="time" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Конец рабочего дня</label>
                    <VInput v-model="staffSettings.workdayEnd" type="time" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Макс. переработка (ч)</label>
                    <VInput v-model="staffSettings.maxOvertimeHours" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Испытательный срок (дней)</label>
                    <VInput v-model="staffSettings.probationDays" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Мин. рейтинг (предупреждение)</label>
                    <VInput v-model="staffSettings.minRatingWarning" type="number" step="0.1" />
                </div>
            </div>
            <div class="mt-4 pt-4 border-t space-y-3" style="border-color:var(--t-border)">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="staffSettings.autoSchedule"
                           class="w-4 h-4 rounded" style="accent-color:var(--t-primary)">
                    <span class="text-sm" style="color:var(--t-text)">Автоматическое составление расписания</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="staffSettings.notifyOnLate"
                           class="w-4 h-4 rounded" style="accent-color:var(--t-primary)">
                    <span class="text-sm" style="color:var(--t-text)">Уведомлять при опоздании</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="staffSettings.notifyOnSick"
                           class="w-4 h-4 rounded" style="accent-color:var(--t-primary)">
                    <span class="text-sm" style="color:var(--t-text)">Уведомлять о больничных</span>
                </label>
            </div>
            <div class="mt-4 pt-3 border-t flex justify-end" style="border-color:var(--t-border)">
                <VButton @click="saveSettings">💾 Сохранить настройки</VButton>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  MODALS                                               -->
    <!-- ══════════════════════════════════════════════════════ -->

    <!-- Add / Edit Employee -->
    <VModal :show="showAddModal" @close="showAddModal = false"
            :title="editingStaff ? '✏️ Редактирование сотрудника' : '➕ Новый сотрудник'">
        <div class="space-y-3">
            <VInput v-model="staffForm.name" placeholder="ФИО сотрудника" />
            <VInput v-model="staffForm.phone" placeholder="Телефон" />
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Должность</label>
                    <select v-model="staffForm.position" class="w-full px-3 py-2 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Выберите</option>
                        <option v-for="p in positions" :key="p" :value="p">{{ p }}</option>
                        <option value="Другое">Другое</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Уровень</label>
                    <select v-model="staffForm.level" class="w-full px-3 py-2 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Без уровня</option>
                        <option>Джуниор</option>
                        <option>Мастер</option>
                        <option>Топ</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:var(--t-text-2)">Салон</label>
                <select v-model="staffForm.salon" class="w-full px-3 py-2 rounded-lg text-sm border"
                        style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                    <option value="">Выберите</option>
                    <option v-for="s in salons" :key="s.id || s.name" :value="s.name || s">{{ s.name || s }}</option>
                </select>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Комиссия (%)</label>
                    <VInput v-model="staffForm.commission" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Оклад (₽)</label>
                    <VInput v-model="staffForm.salary" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Часов/нед.</label>
                    <VInput v-model="staffForm.hoursWeek" type="number" />
                </div>
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showAddModal = false">Отмена</VButton>
            <VButton @click="saveEmployee">{{ editingStaff ? '💾 Сохранить' : '➕ Добавить' }}</VButton>
        </template>
    </VModal>

    <!-- Status Change Modal -->
    <VModal :show="showStatusModal" @close="showStatusModal = false" title="🔄 Изменение статуса">
        <div class="space-y-3">
            <div class="text-sm mb-2" style="color:var(--t-text)">
                Сотрудник: <b>{{ statusTarget?.name }}</b>
            </div>
            <select v-model="newStatus" class="w-full px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showStatusModal = false">Отмена</VButton>
            <VButton @click="applyStatus">✅ Применить</VButton>
        </template>
    </VModal>

    <!-- Mass Fire Confirm -->
    <VModal :show="showMassDeleteModal" @close="showMassDeleteModal = false" title="⚠️ Подтверждение увольнения">
        <div class="text-center py-4">
            <div class="text-4xl mb-3">🚫</div>
            <div class="text-sm" style="color:var(--t-text)">
                Вы действительно хотите уволить <b>{{ selectedStaff.length }}</b> сотрудников?
            </div>
            <div class="text-xs mt-2" style="color:var(--t-text-3)">Статус будет изменён на «Уволен»</div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showMassDeleteModal = false">Отмена</VButton>
            <VButton style="background:#ef4444" @click="confirmMassFire">🚫 Уволить</VButton>
        </template>
    </VModal>

    <!-- Shift Modal -->
    <VModal :show="showShiftModal" @close="showShiftModal = false" title="📅 Редактирование смены">
        <div class="space-y-3">
            <div class="text-sm" style="color:var(--t-text)">
                <b>{{ shiftForm.empName }}</b> · {{ weekDays[shiftForm.dayIdx]?.dayName }} {{ weekDays[shiftForm.dayIdx]?.date }} {{ weekDays[shiftForm.dayIdx]?.month }}
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Начало</label>
                    <VInput v-model="shiftForm.from" type="time" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Конец</label>
                    <VInput v-model="shiftForm.to" type="time" />
                </div>
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" style="color:#ef4444" @click="removeShift">🗑️ Убрать</VButton>
            <VButton variant="outline" @click="showShiftModal = false">Отмена</VButton>
            <VButton @click="saveShift">💾 Сохранить</VButton>
        </template>
    </VModal>

    <!-- Toast -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showToast" class="fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-medium"
                 :style="{ background: 'var(--t-primary)', color: '#fff' }">
                {{ toastMessage }}
            </div>
        </Transition>
    </Teleport>
</div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
