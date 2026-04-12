<script setup>
/**
 * BeautyPublicPages — управление публичными страницами салонов и мастеров.
 * Аналог пабликов VK / Instagram: посты, сторизы, портфолио, отзывы,
 * подписчики, лента, SEO, шаблоны страниц, виджеты записи.
 *
 * 8 табов: обзор, страницы, посты, сторизы, портфолио, отзывы, SEO, виджеты
 *
 * Props: salons, masters
 * Emits: create-page, edit-page, delete-page, publish-post, schedule-post, export-report
 */
import { ref, computed, reactive, watch } from 'vue';
import VCard from '../../UI/VCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VTabs from '../../UI/VTabs.vue';
import VTable from '../../UI/VTable.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    salons:  { type: Array, default: () => [] },
    masters: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'create-page', 'edit-page', 'delete-page',
    'publish-post', 'schedule-post', 'export-report',
]);

/* ── Themes ── */
const themes = {
    mint:    { bg: '#f0fdf4', surface: '#ffffff', border: '#bbf7d0', primary: '#22c55e', primaryDim: '#16a34a', accent: '#10b981', text: '#1e293b', text2: '#475569', text3: '#94a3b8', glow: 'rgba(34,197,94,.18)', header: '#f0fdf4', btn: '#22c55e', btnHover: '#16a34a', cardHover: '#f0fdf4', gradientFrom: '#22c55e', gradientVia: '#10b981', gradientTo: '#059669' },
    day:     { bg: '#fffbeb', surface: '#ffffff', border: '#fde68a', primary: '#f59e0b', primaryDim: '#d97706', accent: '#eab308', text: '#1e293b', text2: '#475569', text3: '#94a3b8', glow: 'rgba(245,158,11,.18)', header: '#fffbeb', btn: '#f59e0b', btnHover: '#d97706', cardHover: '#fffbeb', gradientFrom: '#f59e0b', gradientVia: '#eab308', gradientTo: '#ca8a04' },
    night:   { bg: '#0f172a', surface: '#1e293b', border: '#334155', primary: '#818cf8', primaryDim: '#6366f1', accent: '#a78bfa', text: '#f1f5f9', text2: '#cbd5e1', text3: '#64748b', glow: 'rgba(129,140,248,.18)', header: '#0f172a', btn: '#818cf8', btnHover: '#6366f1', cardHover: '#1e293b', gradientFrom: '#818cf8', gradientVia: '#a78bfa', gradientTo: '#7c3aed' },
    sunset:  { bg: '#fff1f2', surface: '#ffffff', border: '#fecdd3', primary: '#fb7185', primaryDim: '#f43f5e', accent: '#e11d48', text: '#1e293b', text2: '#475569', text3: '#94a3b8', glow: 'rgba(251,113,133,.18)', header: '#fff1f2', btn: '#fb7185', btnHover: '#f43f5e', cardHover: '#fff1f2', gradientFrom: '#fb7185', gradientVia: '#f43f5e', gradientTo: '#e11d48' },
    lavender:{ bg: '#faf5ff', surface: '#ffffff', border: '#e9d5ff', primary: '#a855f7', primaryDim: '#9333ea', accent: '#7c3aed', text: '#1e293b', text2: '#475569', text3: '#94a3b8', glow: 'rgba(168,85,247,.18)', header: '#faf5ff', btn: '#a855f7', btnHover: '#9333ea', cardHover: '#faf5ff', gradientFrom: '#a855f7', gradientVia: '#7c3aed', gradientTo: '#6d28d9' },
};
const currentTheme = ref('mint');
const t = computed(() => themes[currentTheme.value]);

/* ── Toast ── */
const showToast = ref(false);
const toastMessage = ref('');
function toast(msg) {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}

/* ── Inner Tabs ── */
const innerTabs = [
    { key: 'overview',   label: '📊 Обзор' },
    { key: 'pages',      label: '📄 Страницы' },
    { key: 'posts',      label: '✏️ Посты' },
    { key: 'stories',    label: '📸 Сторизы' },
    { key: 'portfolio',  label: '💅 Портфолио' },
    { key: 'reviews',    label: '⭐ Отзывы' },
    { key: 'seo',        label: '🔍 SEO' },
    { key: 'widgets',    label: '🧩 Виджеты' },
];
const activeInner = ref('overview');

/* ═══════════════════════════════════════════════════
   TAB 1 — ОБЗОР (статистика по всем страницам)
   ═══════════════════════════════════════════════════ */
const overviewStats = ref([
    { label: 'Всего страниц',       value: '6',      trend: '+2 за месяц', icon: '📄' },
    { label: 'Подписчиков всего',    value: '12 480', trend: '+1 230',      icon: '👥' },
    { label: 'Просмотров / мес',     value: '87 340', trend: '+22%',        icon: '👁️' },
    { label: 'Постов опубликовано',  value: '142',    trend: '+18 за мес',  icon: '✏️' },
    { label: 'Вовлечённость',        value: '8.4%',   trend: '+1.2%',       icon: '❤️' },
    { label: 'Записей через паблик', value: '324',    trend: '+56',         icon: '📅' },
]);
const topPages = ref([
    { name: 'Салон «Бьюти Лайф»', slug: 'beauty-life', followers: 4280, views7d: 12400, posts: 48, engagement: 9.2, status: 'active' },
    { name: 'Мастер Анна С.',     slug: 'anna-s',       followers: 3120, views7d: 8700,  posts: 36, engagement: 11.4, status: 'active' },
    { name: 'Салон «Гламур»',     slug: 'glamour',      followers: 2890, views7d: 7200,  posts: 32, engagement: 7.8, status: 'active' },
    { name: 'Мастер Ольга Д.',    slug: 'olga-d',       followers: 1480, views7d: 4100,  posts: 19, engagement: 12.1, status: 'active' },
    { name: 'Салон «НейлАрт»',    slug: 'nail-art',     followers: 510,  views7d: 1900,  posts: 7,  engagement: 6.3, status: 'draft' },
    { name: 'Мастер Кристина Л.', slug: 'kristina-l',   followers: 200,  views7d: 680,   posts: 0,  engagement: 0,   status: 'draft' },
]);
const recentActivity = ref([
    { date: '08.04.2026 14:22', page: 'Анна С.', action: 'Опубликован пост «AirTouch — тренд весны»', type: 'post' },
    { date: '08.04.2026 11:05', page: 'Бьюти Лайф', action: 'Добавлена сторис: акция -20% маникюр', type: 'story' },
    { date: '07.04.2026 18:40', page: 'Гламур', action: 'Новый отзыв от Марии К. ⭐⭐⭐⭐⭐', type: 'review' },
    { date: '07.04.2026 16:12', page: 'Ольга Д.', action: 'Добавлены 4 фото в портфолио', type: 'portfolio' },
    { date: '07.04.2026 09:30', page: 'Бьюти Лайф', action: 'SEO-мета обновлено', type: 'seo' },
]);

/* ═══════════════════════════════════════════════════
   TAB 2 — СТРАНИЦЫ (список + CRUD)
   ═══════════════════════════════════════════════════ */
const pages = ref([
    { id: 1, name: 'Салон «Бьюти Лайф»', type: 'salon', slug: 'beauty-life', status: 'published', followers: 4280, views: 87340, cover: '🖼️', template: 'premium', createdAt: '01.02.2026', lastPost: '08.04.2026' },
    { id: 2, name: 'Мастер Анна С.',     type: 'master', slug: 'anna-s',       status: 'published', followers: 3120, views: 52100, cover: '📷', template: 'portfolio', createdAt: '15.01.2026', lastPost: '08.04.2026' },
    { id: 3, name: 'Салон «Гламур»',     type: 'salon', slug: 'glamour',      status: 'published', followers: 2890, views: 41200, cover: '🖼️', template: 'classic', createdAt: '10.03.2026', lastPost: '07.04.2026' },
    { id: 4, name: 'Мастер Ольга Д.',    type: 'master', slug: 'olga-d',       status: 'published', followers: 1480, views: 18900, cover: '📷', template: 'portfolio', createdAt: '20.02.2026', lastPost: '06.04.2026' },
    { id: 5, name: 'Салон «НейлАрт»',    type: 'salon', slug: 'nail-art',     status: 'draft',     followers: 510,  views: 3200,  cover: '🖼️', template: 'minimal', createdAt: '01.04.2026', lastPost: '—' },
    { id: 6, name: 'Мастер Кристина Л.', type: 'master', slug: 'kristina-l',   status: 'draft',     followers: 200,  views: 680,   cover: '📷', template: 'portfolio', createdAt: '05.04.2026', lastPost: '—' },
]);
const pageFilter = ref('all');
const filteredPages = computed(() => {
    if (pageFilter.value === 'all') return pages.value;
    if (pageFilter.value === 'salon') return pages.value.filter(p => p.type === 'salon');
    if (pageFilter.value === 'master') return pages.value.filter(p => p.type === 'master');
    if (pageFilter.value === 'published') return pages.value.filter(p => p.status === 'published');
    if (pageFilter.value === 'draft') return pages.value.filter(p => p.status === 'draft');
    return pages.value;
});
const showCreatePageModal = ref(false);
const newPage = reactive({ name: '', type: 'salon', slug: '', template: 'classic', description: '' });
function createPage() {
    if (!newPage.name.trim()) return;
    const slugAuto = newPage.slug || newPage.name.toLowerCase().replace(/[^a-zа-яё0-9]/gi, '-').replace(/-+/g, '-');
    pages.value.push({
        id: Date.now(),
        name: newPage.name,
        type: newPage.type,
        slug: slugAuto,
        status: 'draft',
        followers: 0,
        views: 0,
        cover: newPage.type === 'salon' ? '🖼️' : '📷',
        template: newPage.template,
        createdAt: new Date().toLocaleDateString('ru-RU'),
        lastPost: '—',
    });
    emit('create-page', { ...newPage, slug: slugAuto });
    showCreatePageModal.value = false;
    Object.assign(newPage, { name: '', type: 'salon', slug: '', template: 'classic', description: '' });
    toast('Страница создана');
}
const showDeletePageModal = ref(false);
const pageToDelete = ref(null);
function confirmDeletePage(page) {
    pageToDelete.value = page;
    showDeletePageModal.value = true;
}
function deletePage() {
    if (!pageToDelete.value) return;
    pages.value = pages.value.filter(p => p.id !== pageToDelete.value.id);
    emit('delete-page', pageToDelete.value.id);
    showDeletePageModal.value = false;
    pageToDelete.value = null;
    toast('Страница удалена');
}
function togglePageStatus(page) {
    page.status = page.status === 'published' ? 'draft' : 'published';
    emit('edit-page', { id: page.id, status: page.status });
    toast(page.status === 'published' ? 'Страница опубликована' : 'Страница снята с публикации');
}

const pageTemplates = ref([
    { key: 'classic',   label: 'Классический',   preview: '📋', desc: 'Лента постов + информация о салоне' },
    { key: 'portfolio', label: 'Портфолио',       preview: '🎨', desc: 'Галерея работ + услуги + запись' },
    { key: 'premium',   label: 'Премиум',         preview: '💎', desc: 'Полноэкранные баннеры + видео + AR-примерка' },
    { key: 'minimal',   label: 'Минимализм',      preview: '✨', desc: 'Только контакты + кнопка записи' },
    { key: 'landing',   label: 'Лэндинг',         preview: '🚀', desc: 'Одностраничник для акции / мастера' },
]);

/* ═══════════════════════════════════════════════════
   TAB 3 — ПОСТЫ
   ═══════════════════════════════════════════════════ */
const posts = ref([
    { id: 1, pageId: 1, pageName: 'Бьюти Лайф', title: 'Новая коллекция весна 2026 💐', excerpt: 'Встречайте свежие тренды...', type: 'text+photo', status: 'published', publishedAt: '08.04.2026 14:22', likes: 234, comments: 18, shares: 12, views: 3400, scheduledAt: null },
    { id: 2, pageId: 2, pageName: 'Анна С.', title: 'AirTouch — тренд весны 🌸', excerpt: 'Показываю процесс окрашивания...', type: 'video', status: 'published', publishedAt: '08.04.2026 10:00', likes: 456, comments: 42, shares: 28, views: 5800, scheduledAt: null },
    { id: 3, pageId: 3, pageName: 'Гламур', title: 'Скидка 20% на маникюр', excerpt: 'Только до конца апреля...', type: 'promo', status: 'published', publishedAt: '07.04.2026 09:00', likes: 189, comments: 7, shares: 34, views: 4200, scheduledAt: null },
    { id: 4, pageId: 4, pageName: 'Ольга Д.', title: 'Мой путь в nail-art 💅', excerpt: 'История моей карьеры...', type: 'text+photo', status: 'published', publishedAt: '06.04.2026 16:30', likes: 312, comments: 29, shares: 15, views: 2900, scheduledAt: null },
    { id: 5, pageId: 1, pageName: 'Бьюти Лайф', title: 'Летние тренды макияжа', excerpt: 'Готовимся к лету...', type: 'text+photo', status: 'scheduled', publishedAt: null, likes: 0, comments: 0, shares: 0, views: 0, scheduledAt: '10.04.2026 09:00' },
    { id: 6, pageId: 2, pageName: 'Анна С.', title: 'До/После: Сложное окрашивание', excerpt: 'Трансформация за 4 часа...', type: 'before-after', status: 'draft', publishedAt: null, likes: 0, comments: 0, shares: 0, views: 0, scheduledAt: null },
    { id: 7, pageId: 1, pageName: 'Бьюти Лайф', title: 'Конкурс подписчиков 🎁', excerpt: 'Разыгрываем сертификат...', type: 'promo', status: 'draft', publishedAt: null, likes: 0, comments: 0, shares: 0, views: 0, scheduledAt: null },
]);
const postFilter = ref('all');
const filteredPosts = computed(() => {
    if (postFilter.value === 'all') return posts.value;
    return posts.value.filter(p => p.status === postFilter.value);
});
const showCreatePostModal = ref(false);
const newPost = reactive({ pageId: null, title: '', content: '', type: 'text+photo', scheduledAt: '' });
function publishPost() {
    if (!newPost.title.trim() || !newPost.pageId) return;
    const page = pages.value.find(p => p.id === newPost.pageId);
    const isScheduled = !!newPost.scheduledAt;
    posts.value.unshift({
        id: Date.now(),
        pageId: newPost.pageId,
        pageName: page?.name || '—',
        title: newPost.title,
        excerpt: newPost.content.substring(0, 60) + '...',
        type: newPost.type,
        status: isScheduled ? 'scheduled' : 'published',
        publishedAt: isScheduled ? null : new Date().toLocaleString('ru-RU'),
        likes: 0, comments: 0, shares: 0, views: 0,
        scheduledAt: isScheduled ? newPost.scheduledAt : null,
    });
    emit(isScheduled ? 'schedule-post' : 'publish-post', { ...newPost });
    showCreatePostModal.value = false;
    Object.assign(newPost, { pageId: null, title: '', content: '', type: 'text+photo', scheduledAt: '' });
    toast(isScheduled ? 'Пост запланирован' : 'Пост опубликован');
}

const postTypes = [
    { key: 'text+photo',   label: '📷 Фото + текст' },
    { key: 'video',        label: '🎬 Видео' },
    { key: 'before-after', label: '🔄 До / После' },
    { key: 'promo',        label: '🏷️ Акция' },
    { key: 'poll',         label: '📊 Опрос' },
    { key: 'carousel',     label: '🎠 Карусель' },
    { key: 'reels',        label: '🎞️ Reels / Шортс' },
];

/* ═══════════════════════════════════════════════════
   TAB 4 — СТОРИЗЫ
   ═══════════════════════════════════════════════════ */
const stories = ref([
    { id: 1, pageId: 1, pageName: 'Бьюти Лайф', type: 'photo', caption: 'Акция -20% маникюр 💅', views: 2100, reactions: 340, link: '/booking?promo=nail20', expiresAt: '09.04.2026 11:05', status: 'active' },
    { id: 2, pageId: 2, pageName: 'Анна С.', type: 'video', caption: 'Процесс окрашивания 🎨', views: 3400, reactions: 520, link: null, expiresAt: '09.04.2026 10:00', status: 'active' },
    { id: 3, pageId: 3, pageName: 'Гламур', type: 'photo', caption: 'Новая палитра OPI 💎', views: 1800, reactions: 210, link: '/products/opi-spring', expiresAt: '08.04.2026 18:00', status: 'active' },
    { id: 4, pageId: 1, pageName: 'Бьюти Лайф', type: 'poll', caption: 'Какой цвет лета? 🌈', views: 4200, reactions: 890, link: null, expiresAt: '08.04.2026 09:00', status: 'expired' },
    { id: 5, pageId: 4, pageName: 'Ольга Д.', type: 'before-after', caption: 'Маникюр до/после ✨', views: 1600, reactions: 280, link: '/booking?master=olga', expiresAt: '08.04.2026 16:12', status: 'expired' },
]);
const storyFilter = ref('all');
const filteredStories = computed(() => {
    if (storyFilter.value === 'all') return stories.value;
    return stories.value.filter(s => s.status === storyFilter.value);
});
const showCreateStoryModal = ref(false);
const newStory = reactive({ pageId: null, type: 'photo', caption: '', link: '', duration: 24 });
function createStory() {
    if (!newStory.pageId || !newStory.caption.trim()) return;
    const page = pages.value.find(p => p.id === newStory.pageId);
    stories.value.unshift({
        id: Date.now(),
        pageId: newStory.pageId,
        pageName: page?.name || '—',
        type: newStory.type,
        caption: newStory.caption,
        views: 0,
        reactions: 0,
        link: newStory.link || null,
        expiresAt: new Date(Date.now() + newStory.duration * 3600000).toLocaleString('ru-RU'),
        status: 'active',
    });
    showCreateStoryModal.value = false;
    Object.assign(newStory, { pageId: null, type: 'photo', caption: '', link: '', duration: 24 });
    toast('Сторис опубликована');
}

/* ═══════════════════════════════════════════════════
   TAB 5 — ПОРТФОЛИО (работы мастеров)
   ═══════════════════════════════════════════════════ */
const portfolioWorks = ref([
    { id: 1, masterId: 1, master: 'Анна С.', category: 'Окрашивание', title: 'AirTouch блонд', before: '🖼️', after: '🖼️', likes: 342, saves: 89, tags: ['блонд', 'airtouch', 'длинные'], date: '08.04.2026' },
    { id: 2, masterId: 1, master: 'Анна С.', category: 'Стрижка', title: 'Каре с удлинением', before: '🖼️', after: '🖼️', likes: 218, saves: 56, tags: ['каре', 'стрижка'], date: '06.04.2026' },
    { id: 3, masterId: 2, master: 'Ольга Д.', category: 'Маникюр', title: 'Французский градиент', before: '🖼️', after: '🖼️', likes: 567, saves: 134, tags: ['маникюр', 'френч', 'градиент'], date: '07.04.2026' },
    { id: 4, masterId: 2, master: 'Ольга Д.', category: 'Маникюр', title: 'Nail Art космос 🌌', before: '🖼️', after: '🖼️', likes: 891, saves: 245, tags: ['nail-art', 'космос', 'дизайн'], date: '05.04.2026' },
    { id: 5, masterId: 3, master: 'Кристина Л.', category: 'Брови', title: 'Микроблейдинг пудровые', before: '🖼️', after: '🖼️', likes: 178, saves: 42, tags: ['брови', 'микроблейдинг', 'пудровые'], date: '04.04.2026' },
    { id: 6, masterId: 3, master: 'Кристина Л.', category: 'Ресницы', title: 'Наращивание 2D', before: '🖼️', after: '🖼️', likes: 156, saves: 38, tags: ['ресницы', 'наращивание', '2D'], date: '03.04.2026' },
]);
const portfolioCategory = ref('all');
const portfolioCategories = ['all', 'Окрашивание', 'Стрижка', 'Маникюр', 'Брови', 'Ресницы', 'Макияж', 'Уход'];
const filteredPortfolio = computed(() => {
    if (portfolioCategory.value === 'all') return portfolioWorks.value;
    return portfolioWorks.value.filter(w => w.category === portfolioCategory.value);
});
const showAddWorkModal = ref(false);
const newWork = reactive({ masterId: null, category: 'Маникюр', title: '', tags: '' });
function addWork() {
    if (!newWork.masterId || !newWork.title.trim()) return;
    const master = props.masters.find(m => m.id === newWork.masterId);
    portfolioWorks.value.unshift({
        id: Date.now(),
        masterId: newWork.masterId,
        master: master?.name || `Мастер #${newWork.masterId}`,
        category: newWork.category,
        title: newWork.title,
        before: '🖼️', after: '🖼️',
        likes: 0, saves: 0,
        tags: newWork.tags.split(',').map(t => t.trim()).filter(Boolean),
        date: new Date().toLocaleDateString('ru-RU'),
    });
    showAddWorkModal.value = false;
    Object.assign(newWork, { masterId: null, category: 'Маникюр', title: '', tags: '' });
    toast('Работа добавлена в портфолио');
}

/* ═══════════════════════════════════════════════════
   TAB 6 — ОТЗЫВЫ
   ═══════════════════════════════════════════════════ */
const reviews = ref([
    { id: 1, pageId: 1, pageName: 'Бьюти Лайф', client: 'Мария К.', rating: 5, text: 'Отличный салон! Всегда довольна результатом. Анна — волшебница 🪄', date: '08.04.2026', reply: 'Спасибо, Мария! Ждём вас снова 💕', status: 'published', photos: 2 },
    { id: 2, pageId: 2, pageName: 'Анна С.', client: 'Елена П.', rating: 5, text: 'Лучшее окрашивание в городе. AirTouch — просто огонь! 🔥', date: '07.04.2026', reply: null, status: 'published', photos: 3 },
    { id: 3, pageId: 3, pageName: 'Гламур', client: 'Дарья В.', rating: 4, text: 'Хороший маникюр, но пришлось немного подождать.', date: '07.04.2026', reply: 'Дарья, извините за ожидание! Работаем над расписанием.', status: 'published', photos: 0 },
    { id: 4, pageId: 4, pageName: 'Ольга Д.', client: 'Ирина М.', rating: 5, text: 'Ольга — мастер nail-art от бога! Космический дизайн 🌌', date: '06.04.2026', reply: null, status: 'published', photos: 1 },
    { id: 5, pageId: 1, pageName: 'Бьюти Лайф', client: 'Анонимный', rating: 2, text: 'Не понравилось обслуживание. Администратор грубила.', date: '05.04.2026', reply: null, status: 'moderation', photos: 0 },
    { id: 6, pageId: 3, pageName: 'Гламур', client: 'Наталья Б.', rating: 5, text: 'Брови и ресницы — идеально. Кристина — профессионал!', date: '04.04.2026', reply: 'Благодарим за тёплые слова! 🙏', status: 'published', photos: 1 },
]);
const reviewFilter = ref('all');
const filteredReviews = computed(() => {
    if (reviewFilter.value === 'all') return reviews.value;
    if (reviewFilter.value === 'moderation') return reviews.value.filter(r => r.status === 'moderation');
    if (reviewFilter.value === 'no-reply') return reviews.value.filter(r => !r.reply);
    return reviews.value;
});
const showReplyModal = ref(false);
const replyTarget = ref(null);
const replyText = ref('');
function openReplyModal(review) {
    replyTarget.value = review;
    replyText.value = review.reply || '';
    showReplyModal.value = true;
}
function saveReply() {
    if (!replyTarget.value || !replyText.value.trim()) return;
    replyTarget.value.reply = replyText.value;
    showReplyModal.value = false;
    toast('Ответ сохранён');
}
function approveReview(review) {
    review.status = 'published';
    toast('Отзыв одобрен');
}
function rejectReview(review) {
    review.status = 'rejected';
    toast('Отзыв отклонён');
}
const avgRating = computed(() => {
    const pub = reviews.value.filter(r => r.status === 'published');
    if (!pub.length) return 0;
    return (pub.reduce((s, r) => s + r.rating, 0) / pub.length).toFixed(1);
});
const reviewsDistribution = computed(() => {
    const dist = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    reviews.value.filter(r => r.status === 'published').forEach(r => { dist[r.rating]++; });
    return dist;
});

/* ═══════════════════════════════════════════════════
   TAB 7 — SEO
   ═══════════════════════════════════════════════════ */
const seoSettings = ref([
    { pageId: 1, pageName: 'Бьюти Лайф', title: 'Салон красоты Бьюти Лайф — стрижки, окрашивание, маникюр | CatVRF', description: 'Лучший салон красоты в центре города. Стрижки, окрашивание AirTouch, маникюр, педикюр. Онлайн-запись 24/7.', keywords: 'салон красоты, стрижка, окрашивание, маникюр, airtouch', ogImage: '🖼️', score: 92 },
    { pageId: 2, pageName: 'Анна С.', title: 'Мастер-колорист Анна С. — окрашивание, стрижки, укладки', description: 'Профессиональный колорист с 8-летним опытом. AirTouch, балаяж, шатуш. Портфолио и отзывы.', keywords: 'колорист, окрашивание, airtouch, балаяж', ogImage: '📷', score: 87 },
    { pageId: 3, pageName: 'Гламур', title: 'Салон Гламур — маникюр, педикюр, нейл-арт', description: 'Салон маникюра и педикюра Гламур. Гель-лак, наращивание, дизайн ногтей.', keywords: 'маникюр, педикюр, гель-лак, нейл-арт', ogImage: '🖼️', score: 78 },
    { pageId: 4, pageName: 'Ольга Д.', title: 'Мастер маникюра Ольга Д. — nail-art, дизайн ногтей', description: 'Авторский nail-art и дизайн ногтей. Уникальные работы, портфолио, онлайн-запись.', keywords: 'nail-art, маникюр, дизайн ногтей', ogImage: '📷', score: 82 },
]);
const showEditSeoModal = ref(false);
const seoEditTarget = ref(null);
const seoEditForm = reactive({ title: '', description: '', keywords: '', ogImage: '' });
function openSeoEditor(seo) {
    seoEditTarget.value = seo;
    Object.assign(seoEditForm, { title: seo.title, description: seo.description, keywords: seo.keywords, ogImage: seo.ogImage });
    showEditSeoModal.value = true;
}
function saveSeo() {
    if (!seoEditTarget.value) return;
    Object.assign(seoEditTarget.value, { title: seoEditForm.title, description: seoEditForm.description, keywords: seoEditForm.keywords, ogImage: seoEditForm.ogImage });
    seoEditTarget.value.score = Math.min(100, Math.round((seoEditForm.title.length > 30 ? 25 : 10) + (seoEditForm.description.length > 80 ? 30 : 15) + (seoEditForm.keywords.split(',').length > 3 ? 25 : 10) + (seoEditForm.ogImage ? 20 : 0)));
    showEditSeoModal.value = false;
    toast('SEO-настройки сохранены');
}

/* ═══════════════════════════════════════════════════
   TAB 8 — ВИДЖЕТЫ ЗАПИСИ И ИНТЕГРАЦИИ
   ═══════════════════════════════════════════════════ */
const widgets = ref([
    { id: 1, pageId: 1, type: 'booking-btn', label: 'Кнопка записи', code: '<div data-catvrf-booking="beauty-life"></div>', installs: 124, clicks: 890, conversions: 67 },
    { id: 2, pageId: 1, type: 'reviews-carousel', label: 'Карусель отзывов', code: '<div data-catvrf-reviews="beauty-life" limit="5"></div>', installs: 48, clicks: 320, conversions: 0 },
    { id: 3, pageId: 2, type: 'portfolio-grid', label: 'Сетка портфолио', code: '<div data-catvrf-portfolio="anna-s" cols="3"></div>', installs: 36, clicks: 210, conversions: 0 },
    { id: 4, pageId: 1, type: 'schedule-embed', label: 'Встроенное расписание', code: '<iframe src="https://catvrf.ru/embed/schedule/beauty-life" width="100%" height="600"></iframe>', installs: 18, clicks: 540, conversions: 42 },
    { id: 5, pageId: 3, type: 'promo-banner', label: 'Промо-баннер', code: '<div data-catvrf-promo="glamour" style="max-width:400px"></div>', installs: 22, clicks: 180, conversions: 15 },
]);
function copyWidgetCode(widget) {
    navigator.clipboard.writeText(widget.code).catch(() => {
        toast('Не удалось скопировать — скопируйте код вручную');
        return;
    });
    toast(`Код виджета «${widget.label}» скопирован`);
}
const showCreateWidgetModal = ref(false);
const newWidget = reactive({ pageId: null, type: 'booking-btn', label: '' });
const widgetTypes = [
    { key: 'booking-btn',       label: '📅 Кнопка записи' },
    { key: 'reviews-carousel',  label: '⭐ Карусель отзывов' },
    { key: 'portfolio-grid',    label: '💅 Сетка портфолио' },
    { key: 'schedule-embed',    label: '🗓️ Встроенное расписание' },
    { key: 'promo-banner',      label: '🏷️ Промо-баннер' },
    { key: 'price-list',        label: '💰 Прайс-лист' },
    { key: 'map-location',      label: '📍 Карта с адресом' },
];
function createWidget() {
    if (!newWidget.pageId || !newWidget.label.trim()) return;
    const page = pages.value.find(p => p.id === newWidget.pageId);
    widgets.value.push({
        id: Date.now(),
        pageId: newWidget.pageId,
        type: newWidget.type,
        label: newWidget.label,
        code: `<div data-catvrf-${newWidget.type}="${page?.slug || 'page'}"></div>`,
        installs: 0, clicks: 0, conversions: 0,
    });
    showCreateWidgetModal.value = false;
    Object.assign(newWidget, { pageId: null, type: 'booking-btn', label: '' });
    toast('Виджет создан');
}

/* ── Export ── */
function exportPages() {
    const csv = ['Страница;Тип;Статус;Подписчики;Просмотры;Постов;Дата создания']
        .concat(pages.value.map(p => `${p.name};${p.type};${p.status};${p.followers};${p.views};0;${p.createdAt}`))
        .join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `beauty_pages_${Date.now()}.csv`; a.click();
    URL.revokeObjectURL(url);
    emit('export-report', 'pages');
    toast('Экспорт страниц → CSV');
}
</script>

<template>
<section class="p-6 min-h-screen transition-colors duration-300" :style="{ background: t.bg, color: t.text }">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">📄 Публичные страницы</h1>
            <p class="text-sm mt-1" :style="{ color: t.text2 }">Управление пабликами салонов и мастеров</p>
        </div>
        <div class="flex items-center gap-2">
            <VButton v-for="th in Object.keys(themes)" :key="th" size="xs"
                :style="{ background: currentTheme === th ? t.primary : t.surface, color: currentTheme === th ? '#fff' : t.text, border: `1px solid ${t.border}` }"
                @click="currentTheme = th">{{ th }}</VButton>
        </div>
    </div>

    <!-- Tabs -->
    <VTabs :tabs="innerTabs" :active="activeInner" @update:active="activeInner = $event" class="mb-6" />

    <!-- ═══ TAB 1: ОБЗОР ═══ -->
    <div v-if="activeInner === 'overview'">
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <VStatCard v-for="s in overviewStats" :key="s.label" :label="s.label" :value="s.value"
                :style="{ background: t.surface, border: `1px solid ${t.border}`, borderRadius: '12px' }">
                <template #icon><span class="text-2xl">{{ s.icon }}</span></template>
                <template #trend><span class="text-xs" :style="{ color: t.primary }">{{ s.trend }}</span></template>
            </VStatCard>
        </div>

        <!-- Top Pages -->
        <VCard class="mb-6" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">🏆 Топ страниц по подписчикам</h3></template>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr :style="{ borderBottom: `2px solid ${t.border}` }">
                            <th class="text-left py-2 px-3">Страница</th>
                            <th class="text-right py-2 px-3">Подписчики</th>
                            <th class="text-right py-2 px-3">Просмотры 7д</th>
                            <th class="text-right py-2 px-3">Постов</th>
                            <th class="text-right py-2 px-3">Вовлечённость</th>
                            <th class="text-center py-2 px-3">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pg in topPages" :key="pg.slug" class="transition-colors" :style="{ borderBottom: `1px solid ${t.border}` }" @mouseenter="$event.target.style.background = t.cardHover" @mouseleave="$event.target.style.background = 'transparent'">
                            <td class="py-2 px-3 font-medium">{{ pg.name }}</td>
                            <td class="py-2 px-3 text-right">{{ pg.followers.toLocaleString('ru-RU') }}</td>
                            <td class="py-2 px-3 text-right">{{ pg.views7d.toLocaleString('ru-RU') }}</td>
                            <td class="py-2 px-3 text-right">{{ pg.posts }}</td>
                            <td class="py-2 px-3 text-right font-semibold" :style="{ color: pg.engagement > 8 ? '#22c55e' : t.text2 }">{{ pg.engagement }}%</td>
                            <td class="py-2 px-3 text-center"><VBadge :variant="pg.status === 'active' ? 'success' : 'default'">{{ pg.status === 'active' ? 'Активна' : 'Черновик' }}</VBadge></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>

        <!-- Recent Activity -->
        <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">📋 Последняя активность</h3></template>
            <div class="space-y-3">
                <div v-for="act in recentActivity" :key="act.date + act.page" class="flex items-start gap-3 p-3 rounded-lg transition-colors" :style="{ background: t.bg }">
                    <span class="text-lg">{{ act.type === 'post' ? '✏️' : act.type === 'story' ? '📸' : act.type === 'review' ? '⭐' : act.type === 'portfolio' ? '💅' : '🔍' }}</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ act.action }}</p>
                        <p class="text-xs" :style="{ color: t.text3 }">{{ act.page }} · {{ act.date }}</p>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ TAB 2: СТРАНИЦЫ ═══ -->
    <div v-if="activeInner === 'pages'">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div class="flex gap-2 flex-wrap">
                <VButton v-for="f in [{k:'all',l:'Все'},{k:'salon',l:'Салоны'},{k:'master',l:'Мастера'},{k:'published',l:'Активные'},{k:'draft',l:'Черновики'}]" :key="f.k" size="sm"
                    :style="{ background: pageFilter === f.k ? t.primary : t.surface, color: pageFilter === f.k ? '#fff' : t.text, border: `1px solid ${t.border}` }"
                    @click="pageFilter = f.k">{{ f.l }}</VButton>
            </div>
            <div class="flex gap-2">
                <VButton size="sm" :style="{ background: t.primary, color: '#fff' }" @click="showCreatePageModal = true">+ Создать страницу</VButton>
                <VButton size="sm" :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="exportPages">📥 Экспорт</VButton>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <VCard v-for="pg in filteredPages" :key="pg.id" :style="{ background: t.surface, border: `1px solid ${t.border}` }" class="transition-transform hover:scale-[1.01]">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="font-semibold">{{ pg.cover }} {{ pg.name }}</h4>
                            <p class="text-xs" :style="{ color: t.text3 }">{{ pg.type === 'salon' ? 'Салон' : 'Мастер' }} · /{{ pg.slug }}</p>
                        </div>
                        <VBadge :variant="pg.status === 'published' ? 'success' : 'warning'">{{ pg.status === 'published' ? '✅ Активна' : '📝 Черновик' }}</VBadge>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs mb-3">
                        <div class="p-2 rounded-lg" :style="{ background: t.bg }">
                            <div class="font-bold text-base">{{ pg.followers.toLocaleString('ru-RU') }}</div>
                            <div :style="{ color: t.text3 }">подписчиков</div>
                        </div>
                        <div class="p-2 rounded-lg" :style="{ background: t.bg }">
                            <div class="font-bold text-base">{{ pg.views.toLocaleString('ru-RU') }}</div>
                            <div :style="{ color: t.text3 }">просмотров</div>
                        </div>
                        <div class="p-2 rounded-lg" :style="{ background: t.bg }">
                            <div class="font-bold text-base">{{ pg.template }}</div>
                            <div :style="{ color: t.text3 }">шаблон</div>
                        </div>
                    </div>
                    <p class="text-xs mb-3" :style="{ color: t.text3 }">Создана: {{ pg.createdAt }} · Посл. пост: {{ pg.lastPost }}</p>
                    <div class="flex gap-2">
                        <VButton size="xs" :style="{ background: t.primary, color: '#fff' }" @click="togglePageStatus(pg)">{{ pg.status === 'published' ? 'Снять' : 'Опубликовать' }}</VButton>
                        <VButton size="xs" :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="emit('edit-page', pg)">✏️</VButton>
                        <VButton size="xs" :style="{ background: '#fef2f2', color: '#ef4444', border: '1px solid #fecaca' }" @click="confirmDeletePage(pg)">🗑️</VButton>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Templates preview -->
        <VCard class="mt-6" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">🎨 Шаблоны страниц</h3></template>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 p-4">
                <div v-for="tmpl in pageTemplates" :key="tmpl.key" class="text-center p-4 rounded-xl transition-all cursor-pointer" :style="{ background: t.bg, border: `1px solid ${t.border}` }">
                    <div class="text-4xl mb-2">{{ tmpl.preview }}</div>
                    <div class="font-medium text-sm">{{ tmpl.label }}</div>
                    <div class="text-xs mt-1" :style="{ color: t.text3 }">{{ tmpl.desc }}</div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ TAB 3: ПОСТЫ ═══ -->
    <div v-if="activeInner === 'posts'">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div class="flex gap-2 flex-wrap">
                <VButton v-for="f in [{k:'all',l:'Все'},{k:'published',l:'Опубликованные'},{k:'scheduled',l:'Запланированные'},{k:'draft',l:'Черновики'}]" :key="f.k" size="sm"
                    :style="{ background: postFilter === f.k ? t.primary : t.surface, color: postFilter === f.k ? '#fff' : t.text, border: `1px solid ${t.border}` }"
                    @click="postFilter = f.k">{{ f.l }}</VButton>
            </div>
            <VButton size="sm" :style="{ background: t.primary, color: '#fff' }" @click="showCreatePostModal = true">+ Новый пост</VButton>
        </div>

        <div class="space-y-4">
            <VCard v-for="post in filteredPosts" :key="post.id" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <VBadge :variant="post.status === 'published' ? 'success' : post.status === 'scheduled' ? 'info' : 'default'">{{ post.status === 'published' ? '✅ Опубликован' : post.status === 'scheduled' ? '⏰ Запланирован' : '📝 Черновик' }}</VBadge>
                                <VBadge>{{ postTypes.find(pt => pt.key === post.type)?.label || post.type }}</VBadge>
                            </div>
                            <h4 class="font-semibold text-lg">{{ post.title }}</h4>
                            <p class="text-sm" :style="{ color: t.text2 }">{{ post.excerpt }}</p>
                        </div>
                        <span class="text-xs whitespace-nowrap" :style="{ color: t.text3 }">{{ post.pageName }}</span>
                    </div>
                    <div class="flex items-center gap-4 mt-3 text-sm" :style="{ color: t.text2 }">
                        <span>❤️ {{ post.likes }}</span>
                        <span>💬 {{ post.comments }}</span>
                        <span>🔁 {{ post.shares }}</span>
                        <span>👁️ {{ post.views.toLocaleString('ru-RU') }}</span>
                        <span class="ml-auto text-xs" :style="{ color: t.text3 }">{{ post.publishedAt || `Запланирован: ${post.scheduledAt}` }}</span>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ TAB 4: СТОРИЗЫ ═══ -->
    <div v-if="activeInner === 'stories'">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div class="flex gap-2">
                <VButton v-for="f in [{k:'all',l:'Все'},{k:'active',l:'Активные'},{k:'expired',l:'Истёкшие'}]" :key="f.k" size="sm"
                    :style="{ background: storyFilter === f.k ? t.primary : t.surface, color: storyFilter === f.k ? '#fff' : t.text, border: `1px solid ${t.border}` }"
                    @click="storyFilter = f.k">{{ f.l }}</VButton>
            </div>
            <VButton size="sm" :style="{ background: t.primary, color: '#fff' }" @click="showCreateStoryModal = true">+ Новая сторис</VButton>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <VCard v-for="s in filteredStories" :key="s.id" :style="{ background: t.surface, border: `1px solid ${t.border}`, opacity: s.status === 'expired' ? 0.6 : 1 }">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <VBadge :variant="s.status === 'active' ? 'success' : 'default'">{{ s.status === 'active' ? '🟢 Активна' : '⏰ Истекла' }}</VBadge>
                        <span class="text-xs" :style="{ color: t.text3 }">{{ s.pageName }}</span>
                    </div>
                    <p class="font-medium mb-2">{{ s.caption }}</p>
                    <div class="flex gap-4 text-sm" :style="{ color: t.text2 }">
                        <span>👁️ {{ s.views.toLocaleString('ru-RU') }}</span>
                        <span>❤️ {{ s.reactions }}</span>
                    </div>
                    <p class="text-xs mt-2" :style="{ color: t.text3 }">{{ s.link ? `🔗 ${s.link}` : 'Без ссылки' }} · До {{ s.expiresAt }}</p>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ TAB 5: ПОРТФОЛИО ═══ -->
    <div v-if="activeInner === 'portfolio'">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div class="flex gap-2 flex-wrap">
                <VButton v-for="cat in portfolioCategories" :key="cat" size="sm"
                    :style="{ background: portfolioCategory === cat ? t.primary : t.surface, color: portfolioCategory === cat ? '#fff' : t.text, border: `1px solid ${t.border}` }"
                    @click="portfolioCategory = cat">{{ cat === 'all' ? 'Все' : cat }}</VButton>
            </div>
            <VButton size="sm" :style="{ background: t.primary, color: '#fff' }" @click="showAddWorkModal = true">+ Добавить работу</VButton>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <VCard v-for="w in filteredPortfolio" :key="w.id" :style="{ background: t.surface, border: `1px solid ${t.border}` }" class="transition-transform hover:scale-[1.01]">
                <div class="p-4">
                    <div class="flex justify-between items-center mb-2">
                        <VBadge>{{ w.category }}</VBadge>
                        <span class="text-xs" :style="{ color: t.text3 }">{{ w.master }} · {{ w.date }}</span>
                    </div>
                    <h4 class="font-semibold mb-2">{{ w.title }}</h4>
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div class="text-center p-6 rounded-lg text-3xl" :style="{ background: t.bg }">{{ w.before }}<div class="text-xs mt-1" :style="{ color: t.text3 }">До</div></div>
                        <div class="text-center p-6 rounded-lg text-3xl" :style="{ background: t.bg }">{{ w.after }}<div class="text-xs mt-1" :style="{ color: t.text3 }">После</div></div>
                    </div>
                    <div class="flex items-center gap-4 text-sm" :style="{ color: t.text2 }">
                        <span>❤️ {{ w.likes }}</span>
                        <span>🔖 {{ w.saves }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1 mt-2">
                        <span v-for="tag in w.tags" :key="tag" class="text-xs px-2 py-0.5 rounded-full" :style="{ background: t.glow, color: t.primary }">{{ tag }}</span>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ TAB 6: ОТЗЫВЫ ═══ -->
    <div v-if="activeInner === 'reviews'">
        <!-- Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <VStatCard label="Средний рейтинг" :value="`⭐ ${avgRating}`" :style="{ background: t.surface, border: `1px solid ${t.border}` }" />
            <VStatCard label="Всего отзывов" :value="String(reviews.length)" :style="{ background: t.surface, border: `1px solid ${t.border}` }" />
            <VStatCard label="Без ответа" :value="String(reviews.filter(r => !r.reply).length)" :style="{ background: t.surface, border: `1px solid ${t.border}` }" />
            <VStatCard label="На модерации" :value="String(reviews.filter(r => r.status === 'moderation').length)" :style="{ background: t.surface, border: `1px solid ${t.border}` }" />
        </div>

        <!-- Distribution -->
        <VCard class="mb-6" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">📊 Распределение оценок</h3></template>
            <div class="p-4 space-y-2">
                <div v-for="r in [5,4,3,2,1]" :key="r" class="flex items-center gap-3">
                    <span class="w-6 text-sm font-bold">{{ r }}⭐</span>
                    <div class="flex-1 h-4 rounded-full overflow-hidden" :style="{ background: t.bg }">
                        <div class="h-full rounded-full transition-all" :style="{ width: reviews.filter(rv => rv.status === 'published').length ? ((reviewsDistribution[r] / reviews.filter(rv => rv.status === 'published').length) * 100) + '%' : '0%', background: r >= 4 ? t.primary : r === 3 ? '#eab308' : '#ef4444' }"></div>
                    </div>
                    <span class="text-sm w-8 text-right" :style="{ color: t.text2 }">{{ reviewsDistribution[r] }}</span>
                </div>
            </div>
        </VCard>

        <!-- Filters & Reviews -->
        <div class="flex gap-2 mb-4">
            <VButton v-for="f in [{k:'all',l:'Все'},{k:'moderation',l:'🔶 На модерации'},{k:'no-reply',l:'💬 Без ответа'}]" :key="f.k" size="sm"
                :style="{ background: reviewFilter === f.k ? t.primary : t.surface, color: reviewFilter === f.k ? '#fff' : t.text, border: `1px solid ${t.border}` }"
                @click="reviewFilter = f.k">{{ f.l }}</VButton>
        </div>

        <div class="space-y-4">
            <VCard v-for="r in filteredReviews" :key="r.id" :style="{ background: t.surface, border: `1px solid ${r.status === 'moderation' ? '#fbbf24' : t.border}` }">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <span class="font-semibold">{{ r.client }}</span>
                            <span class="ml-2">{{ '⭐'.repeat(r.rating) }}</span>
                            <span v-if="r.photos" class="ml-2 text-xs" :style="{ color: t.text3 }">📷 {{ r.photos }} фото</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <VBadge>{{ r.pageName }}</VBadge>
                            <span class="text-xs" :style="{ color: t.text3 }">{{ r.date }}</span>
                        </div>
                    </div>
                    <p class="text-sm mb-2">{{ r.text }}</p>
                    <div v-if="r.reply" class="p-3 rounded-lg text-sm" :style="{ background: t.bg }">
                        <span class="font-medium">💬 Ответ:</span> {{ r.reply }}
                    </div>
                    <div class="flex gap-2 mt-3">
                        <VButton size="xs" :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="openReplyModal(r)">{{ r.reply ? '✏️ Ред. ответ' : '💬 Ответить' }}</VButton>
                        <VButton v-if="r.status === 'moderation'" size="xs" :style="{ background: '#dcfce7', color: '#16a34a', border: '1px solid #bbf7d0' }" @click="approveReview(r)">✅ Одобрить</VButton>
                        <VButton v-if="r.status === 'moderation'" size="xs" :style="{ background: '#fef2f2', color: '#ef4444', border: '1px solid #fecaca' }" @click="rejectReview(r)">❌ Отклонить</VButton>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ TAB 7: SEO ═══ -->
    <div v-if="activeInner === 'seo'">
        <div class="space-y-4">
            <VCard v-for="seo in seoSettings" :key="seo.pageId" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="font-semibold text-lg">{{ seo.pageName }}</h4>
                        <div class="flex items-center gap-2">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg" :style="{ background: seo.score >= 80 ? '#dcfce7' : seo.score >= 60 ? '#fef9c3' : '#fef2f2', color: seo.score >= 80 ? '#16a34a' : seo.score >= 60 ? '#ca8a04' : '#ef4444' }">{{ seo.score }}</div>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="font-medium" :style="{ color: t.text2 }">Title:</span>
                            <p>{{ seo.title }}</p>
                        </div>
                        <div>
                            <span class="font-medium" :style="{ color: t.text2 }">Description:</span>
                            <p :style="{ color: t.text2 }">{{ seo.description }}</p>
                        </div>
                        <div>
                            <span class="font-medium" :style="{ color: t.text2 }">Keywords:</span>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <span v-for="kw in seo.keywords.split(',')" :key="kw" class="text-xs px-2 py-0.5 rounded-full" :style="{ background: t.glow, color: t.primary }">{{ kw.trim() }}</span>
                            </div>
                        </div>
                    </div>
                    <VButton size="sm" class="mt-3" :style="{ background: t.primary, color: '#fff' }" @click="openSeoEditor(seo)">✏️ Редактировать SEO</VButton>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ TAB 8: ВИДЖЕТЫ ═══ -->
    <div v-if="activeInner === 'widgets'">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg">🧩 Виджеты для сайтов и соцсетей</h3>
            <VButton size="sm" :style="{ background: t.primary, color: '#fff' }" @click="showCreateWidgetModal = true">+ Создать виджет</VButton>
        </div>

        <div class="space-y-4">
            <VCard v-for="w in widgets" :key="w.id" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h4 class="font-semibold">{{ w.label }}</h4>
                            <p class="text-xs" :style="{ color: t.text3 }">Страница: {{ pages.find(p => p.id === w.pageId)?.name || '—' }}</p>
                        </div>
                        <VBadge>{{ widgetTypes.find(wt => wt.key === w.type)?.label || w.type }}</VBadge>
                    </div>
                    <div class="p-3 rounded-lg font-mono text-xs mb-3 overflow-x-auto" :style="{ background: t.bg, border: `1px solid ${t.border}` }">{{ w.code }}</div>
                    <div class="flex items-center gap-4 text-sm" :style="{ color: t.text2 }">
                        <span>📥 {{ w.installs }} установок</span>
                        <span>👆 {{ w.clicks }} кликов</span>
                        <span>🎯 {{ w.conversions }} конверсий</span>
                    </div>
                    <VButton size="xs" class="mt-3" :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="copyWidgetCode(w)">📋 Копировать код</VButton>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ MODALS ═══ -->

    <!-- Create Page Modal -->
    <VModal :open="showCreatePageModal" title="Создать публичную страницу" @close="showCreatePageModal = false">
        <div class="space-y-4">
            <VInput label="Название страницы" v-model="newPage.name" placeholder="Салон «Мой салон»" />
            <div>
                <label class="block text-sm font-medium mb-1">Тип</label>
                <select v-model="newPage.type" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option value="salon">Салон</option>
                    <option value="master">Мастер</option>
                </select>
            </div>
            <VInput label="Slug (URL)" v-model="newPage.slug" placeholder="my-salon" />
            <div>
                <label class="block text-sm font-medium mb-1">Шаблон</label>
                <select v-model="newPage.template" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option v-for="tmpl in pageTemplates" :key="tmpl.key" :value="tmpl.key">{{ tmpl.label }} — {{ tmpl.desc }}</option>
                </select>
            </div>
            <VInput label="Описание" v-model="newPage.description" placeholder="Краткое описание..." />
            <div class="flex justify-end gap-2">
                <VButton :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="showCreatePageModal = false">Отмена</VButton>
                <VButton :style="{ background: t.primary, color: '#fff' }" @click="createPage">Создать</VButton>
            </div>
        </div>
    </VModal>

    <!-- Delete Page Modal -->
    <VModal :open="showDeletePageModal" title="Удалить страницу?" @close="showDeletePageModal = false">
        <p class="mb-4">Вы уверены, что хотите удалить страницу <strong>{{ pageToDelete?.name }}</strong>? Все посты, сторизы и виджеты будут удалены.</p>
        <div class="flex justify-end gap-2">
            <VButton :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="showDeletePageModal = false">Отмена</VButton>
            <VButton :style="{ background: '#ef4444', color: '#fff' }" @click="deletePage">Удалить</VButton>
        </div>
    </VModal>

    <!-- Create Post Modal -->
    <VModal :open="showCreatePostModal" title="Новый пост" @close="showCreatePostModal = false">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Страница</label>
                <select v-model="newPost.pageId" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option :value="null" disabled>Выберите страницу</option>
                    <option v-for="pg in pages.filter(p => p.status === 'published')" :key="pg.id" :value="pg.id">{{ pg.name }}</option>
                </select>
            </div>
            <VInput label="Заголовок" v-model="newPost.title" placeholder="Заголовок поста..." />
            <div>
                <label class="block text-sm font-medium mb-1">Контент</label>
                <textarea v-model="newPost.content" rows="4" class="w-full px-3 py-2 rounded-lg text-sm resize-y" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }" placeholder="Текст поста..."></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Тип</label>
                <select v-model="newPost.type" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option v-for="pt in postTypes" :key="pt.key" :value="pt.key">{{ pt.label }}</option>
                </select>
            </div>
            <VInput label="Отложить публикацию (дата+время)" v-model="newPost.scheduledAt" placeholder="10.04.2026 09:00" />
            <div class="flex justify-end gap-2">
                <VButton :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="showCreatePostModal = false">Отмена</VButton>
                <VButton :style="{ background: t.primary, color: '#fff' }" @click="publishPost">{{ newPost.scheduledAt ? '⏰ Запланировать' : '📤 Опубликовать' }}</VButton>
            </div>
        </div>
    </VModal>

    <!-- Create Story Modal -->
    <VModal :open="showCreateStoryModal" title="Новая сторис" @close="showCreateStoryModal = false">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Страница</label>
                <select v-model="newStory.pageId" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option :value="null" disabled>Выберите страницу</option>
                    <option v-for="pg in pages.filter(p => p.status === 'published')" :key="pg.id" :value="pg.id">{{ pg.name }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Тип</label>
                <select v-model="newStory.type" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option value="photo">📷 Фото</option>
                    <option value="video">🎬 Видео</option>
                    <option value="poll">📊 Опрос</option>
                    <option value="before-after">🔄 До / После</option>
                </select>
            </div>
            <VInput label="Подпись" v-model="newStory.caption" placeholder="Подпись к сторис..." />
            <VInput label="Ссылка (CTA)" v-model="newStory.link" placeholder="/booking?promo=..." />
            <VInput label="Длительность (часов)" v-model="newStory.duration" type="number" />
            <div class="flex justify-end gap-2">
                <VButton :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="showCreateStoryModal = false">Отмена</VButton>
                <VButton :style="{ background: t.primary, color: '#fff' }" @click="createStory">📸 Опубликовать</VButton>
            </div>
        </div>
    </VModal>

    <!-- Add Portfolio Work Modal -->
    <VModal :open="showAddWorkModal" title="Добавить работу в портфолио" @close="showAddWorkModal = false">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Мастер</label>
                <select v-model="newWork.masterId" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option :value="null" disabled>Выберите мастера</option>
                    <option v-for="m in masters" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Категория</label>
                <select v-model="newWork.category" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option v-for="c in portfolioCategories.filter(c => c !== 'all')" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>
            <VInput label="Название" v-model="newWork.title" placeholder="AirTouch блонд" />
            <VInput label="Теги (через запятую)" v-model="newWork.tags" placeholder="блонд, airtouch, длинные" />
            <div class="flex justify-end gap-2">
                <VButton :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="showAddWorkModal = false">Отмена</VButton>
                <VButton :style="{ background: t.primary, color: '#fff' }" @click="addWork">💅 Добавить</VButton>
            </div>
        </div>
    </VModal>

    <!-- Reply to Review Modal -->
    <VModal :open="showReplyModal" title="Ответ на отзыв" @close="showReplyModal = false">
        <div class="space-y-4">
            <div v-if="replyTarget" class="p-3 rounded-lg" :style="{ background: t.bg }">
                <p class="font-medium">{{ replyTarget.client }} · {{ '⭐'.repeat(replyTarget.rating) }}</p>
                <p class="text-sm mt-1" :style="{ color: t.text2 }">{{ replyTarget.text }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Ваш ответ</label>
                <textarea v-model="replyText" rows="3" class="w-full px-3 py-2 rounded-lg text-sm resize-y" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }" placeholder="Спасибо за отзыв..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <VButton :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="showReplyModal = false">Отмена</VButton>
                <VButton :style="{ background: t.primary, color: '#fff' }" @click="saveReply">💬 Сохранить</VButton>
            </div>
        </div>
    </VModal>

    <!-- Edit SEO Modal -->
    <VModal :open="showEditSeoModal" title="Настройки SEO" @close="showEditSeoModal = false">
        <div class="space-y-4">
            <VInput label="Title (до 70 символов)" v-model="seoEditForm.title" />
            <div>
                <label class="block text-sm font-medium mb-1">Description (до 160 символов)</label>
                <textarea v-model="seoEditForm.description" rows="3" class="w-full px-3 py-2 rounded-lg text-sm resize-y" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }"></textarea>
                <p class="text-xs mt-1" :style="{ color: seoEditForm.description.length > 160 ? '#ef4444' : t.text3 }">{{ seoEditForm.description.length }}/160</p>
            </div>
            <VInput label="Keywords (через запятую)" v-model="seoEditForm.keywords" />
            <VInput label="OG Image URL" v-model="seoEditForm.ogImage" placeholder="https://..." />
            <div class="flex justify-end gap-2">
                <VButton :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="showEditSeoModal = false">Отмена</VButton>
                <VButton :style="{ background: t.primary, color: '#fff' }" @click="saveSeo">💾 Сохранить</VButton>
            </div>
        </div>
    </VModal>

    <!-- Create Widget Modal -->
    <VModal :open="showCreateWidgetModal" title="Создать виджет" @close="showCreateWidgetModal = false">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Страница</label>
                <select v-model="newWidget.pageId" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option :value="null" disabled>Выберите страницу</option>
                    <option v-for="pg in pages" :key="pg.id" :value="pg.id">{{ pg.name }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Тип виджета</label>
                <select v-model="newWidget.type" class="w-full px-3 py-2 rounded-lg text-sm" :style="{ background: t.bg, border: `1px solid ${t.border}`, color: t.text }">
                    <option v-for="wt in widgetTypes" :key="wt.key" :value="wt.key">{{ wt.label }}</option>
                </select>
            </div>
            <VInput label="Название" v-model="newWidget.label" placeholder="Кнопка записи для сайта" />
            <div class="flex justify-end gap-2">
                <VButton :style="{ background: t.surface, border: `1px solid ${t.border}`, color: t.text }" @click="showCreateWidgetModal = false">Отмена</VButton>
                <VButton :style="{ background: t.primary, color: '#fff' }" @click="createWidget">🧩 Создать</VButton>
            </div>
        </div>
    </VModal>

    <!-- Toast -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showToast" class="fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-medium" :style="{ background: t.primary, color: '#fff' }">{{ toastMessage }}</div>
        </Transition>
    </Teleport>
</section>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
