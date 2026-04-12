/**
 * Каталог вертикалей Экосистемы будущего
 * 12 мега-категорий × ~10-11 вертикалей = 127 всего
 *
 * Каждая вертикаль имеет type:
 *   product   — товар (Купить)
 *   service   — услуга (Записаться)
 *   transport — транспорт (Заказать)
 *   booking   — бронирование (Забронировать)
 *   food      — еда/доставка (Заказать)
 *   event     — мероприятие (Купить билет)
 */

/** Метаданные типов: CTA, префикс цены, бейдж */
export const typeMeta = {
    product:   { cta: 'Купить',        pricePrefix: '',   badge: 'Товар'     },
    service:   { cta: 'Записаться',    pricePrefix: 'от', badge: 'Услуга'    },
    transport: { cta: 'Заказать',      pricePrefix: 'от', badge: 'Транспорт' },
    booking:   { cta: 'Забронировать', pricePrefix: 'от', badge: 'Бронь'     },
    food:      { cta: 'Заказать',      pricePrefix: '',   badge: 'Еда'       },
    event:     { cta: 'Купить билет',  pricePrefix: 'от', badge: 'Событие'   },
};

export const megaCategories = [
    {
        id: 'beauty',
        name: 'Красота',
        icon: '💄',
        desc: 'Салоны, мастера, косметика, SPA',
        count: 312,
        verticals: [
            { name: 'Салоны красоты',       slug: 'beauty-salons', icon: '💇', type: 'service' },
            { name: 'Барбершопы',           slug: 'barbershops',   icon: '💈', type: 'service' },
            { name: 'Маникюр и педикюр',    slug: 'nails',         icon: '💅', type: 'service' },
            { name: 'Косметика',            slug: 'cosmetics',     icon: '🧴', type: 'product' },
            { name: 'SPA и массаж',         slug: 'spa',           icon: '🧖', type: 'service' },
            { name: 'Тату и пирсинг',       slug: 'tattoo',        icon: '🖋️', type: 'service' },
            { name: 'Визажисты',            slug: 'makeup',        icon: '💄', type: 'service' },
            { name: 'Наращивание ресниц',   slug: 'lashes',        icon: '👁️', type: 'service' },
            { name: 'Брови',                slug: 'brows',         icon: '🖌️', type: 'service' },
            { name: 'Парфюмерия',           slug: 'perfume',       icon: '🌸', type: 'product' },
            { name: 'Трихология',           slug: 'trichology',    icon: '🧬', type: 'service' },
            { name: 'Эпиляция',             slug: 'epilation',     icon: '✨', type: 'service' },
        ],
    },
    {
        id: 'health',
        name: 'Здоровье',
        icon: '🏥',
        desc: 'Клиники, аптеки, фитнес, диетология',
        count: 245,
        verticals: [
            { name: 'Клиники',         slug: 'clinics',          icon: '🏥', type: 'service' },
            { name: 'Аптеки',          slug: 'pharmacy',         icon: '💊', type: 'product' },
            { name: 'Стоматология',    slug: 'dentistry',        icon: '🦷', type: 'service' },
            { name: 'Офтальмология',   slug: 'ophthalmology',    icon: '👁️', type: 'service' },
            { name: 'Лаборатории',     slug: 'labs',             icon: '🔬', type: 'service' },
            { name: 'Психология',      slug: 'psychology',       icon: '🧠', type: 'service' },
            { name: 'Телемедицина',    slug: 'telemedicine',     icon: '📱', type: 'service' },
            { name: 'Фитнес',         slug: 'fitness',          icon: '💪', type: 'service' },
            { name: 'Йога и медитация', slug: 'yoga',            icon: '🧘', type: 'service' },
            { name: 'Спортпитание',    slug: 'sports-nutrition', icon: '🥤', type: 'product' },
            { name: 'Диетология',      slug: 'dietology',        icon: '🥗', type: 'service' },
        ],
    },
    {
        id: 'food',
        name: 'Еда и рестораны',
        icon: '🍽️',
        desc: 'Рестораны, доставка, фермеры, рецепты',
        count: 430,
        verticals: [
            { name: 'Рестораны',            slug: 'restaurants',   icon: '🍕', type: 'food'    },
            { name: 'Доставка еды',         slug: 'food-delivery', icon: '🛵', type: 'food'    },
            { name: 'Кофейни',              slug: 'coffee',        icon: '☕', type: 'food'    },
            { name: 'Пиццерии',             slug: 'pizza',         icon: '🍕', type: 'food'    },
            { name: 'Суши и азиатская',     slug: 'sushi',         icon: '🍱', type: 'food'    },
            { name: 'Кондитерские',         slug: 'confectionery', icon: '🎂', type: 'food'    },
            { name: 'Мясные лавки',         slug: 'meat-shops',    icon: '🥩', type: 'product' },
            { name: 'Фермерские продукты',  slug: 'farm-direct',   icon: '🌾', type: 'product' },
            { name: 'Продуктовые магазины', slug: 'grocery',       icon: '🛒', type: 'product' },
            { name: 'Веган-продукты',       slug: 'vegan',         icon: '🥬', type: 'product' },
            { name: 'Кейтеринг',            slug: 'catering',      icon: '🍱', type: 'food'    },
            { name: 'AI-рецепты',           slug: 'ai-recipes',    icon: '🤖', type: 'food'    },
        ],
    },
    {
        id: 'fashion',
        name: 'Мода и стиль',
        icon: '👗',
        desc: 'Одежда, обувь, люкс, AI-стилист',
        count: 520,
        verticals: [
            { name: 'Одежда',            slug: 'clothing',    icon: '👗', type: 'product' },
            { name: 'Обувь',             slug: 'shoes',       icon: '👟', type: 'product' },
            { name: 'Аксессуары',        slug: 'accessories', icon: '👜', type: 'product' },
            { name: 'Люксовые бренды',   slug: 'luxury',      icon: '💎', type: 'product' },
            { name: 'Ювелирные изделия', slug: 'jewelry',     icon: '💍', type: 'product' },
            { name: 'Цветы',            slug: 'flowers',     icon: '💐', type: 'product' },
            { name: 'Подарки',          slug: 'gifts',       icon: '🎁', type: 'product' },
            { name: 'Секонд-хенд',      slug: 'secondhand',  icon: '♻️', type: 'product' },
            { name: 'AI-стилист',       slug: 'ai-stylist',  icon: '🤖', type: 'service' },
            { name: 'Ателье',          slug: 'atelier',     icon: '🧵', type: 'service' },
        ],
    },
    {
        id: 'home',
        name: 'Дом и ремонт',
        icon: '🏠',
        desc: 'Мебель, интерьер, стройка, уборка',
        count: 380,
        verticals: [
            { name: 'Мебель',              slug: 'furniture',     icon: '🛋️', type: 'product' },
            { name: 'Товары для дома',     slug: 'household',     icon: '🏡', type: 'product' },
            { name: 'Ремонт и стройка',    slug: 'construction',  icon: '🔨', type: 'service' },
            { name: 'Сантехника',          slug: 'plumbing',      icon: '🚿', type: 'service' },
            { name: 'Электрика',           slug: 'electrical',    icon: '⚡', type: 'service' },
            { name: 'Уборка',             slug: 'cleaning',      icon: '🧹', type: 'service' },
            { name: 'Бытовые услуги',      slug: 'home-services', icon: '🔧', type: 'service' },
            { name: 'Садоводство',         slug: 'gardening',     icon: '🌱', type: 'product' },
            { name: 'AI-дизайн интерьера', slug: 'ai-interior',   icon: '🤖', type: 'service' },
            { name: 'Климат-техника',      slug: 'climate',       icon: '❄️', type: 'product' },
            { name: 'Текстиль',           slug: 'textile',       icon: '🧶', type: 'product' },
        ],
    },
    {
        id: 'auto',
        name: 'Авто и транспорт',
        icon: '🚗',
        desc: 'Запчасти, СТО, такси, аренда',
        count: 210,
        verticals: [
            { name: 'Автозапчасти',    slug: 'auto-parts',     icon: '🔩', type: 'product'   },
            { name: 'СТО и ремонт',    slug: 'auto-repair',    icon: '🔧', type: 'service'   },
            { name: 'Такси',           slug: 'taxi',           icon: '🚕', type: 'transport'  },
            { name: 'Аренда авто',     slug: 'car-rental',     icon: '🚙', type: 'transport'  },
            { name: 'Тюнинг',         slug: 'tuning',         icon: '🏎️', type: 'service'   },
            { name: 'Автомойки',       slug: 'car-wash',       icon: '🧽', type: 'service'   },
            { name: 'Шины и диски',    slug: 'tires',          icon: '🛞', type: 'product'   },
            { name: 'Автострахование', slug: 'auto-insurance', icon: '🛡️', type: 'service'   },
            { name: 'Эвакуатор',       slug: 'tow-truck',      icon: '🚛', type: 'transport'  },
            { name: 'Каршеринг',       slug: 'carsharing',     icon: '🔑', type: 'transport'  },
        ],
    },
    {
        id: 'travel',
        name: 'Путешествия',
        icon: '✈️',
        desc: 'Отели, хостелы, апарты, загородный отдых, туры',
        count: 175,
        verticals: [
            { name: 'Отели',                 slug: 'hotels',           icon: '🏨', type: 'booking'   },
            { name: 'Хостелы',               slug: 'hostels',          icon: '🛏️', type: 'booking'   },
            { name: 'Апартаменты',           slug: 'apartments',       icon: '🏘️', type: 'booking'   },
            { name: 'Квартиры посуточно',    slug: 'daily-rent',       icon: '🏠', type: 'booking'   },
            { name: 'Пансионаты',            slug: 'boarding-houses',  icon: '🏡', type: 'booking'   },
            { name: 'Базы отдыха',           slug: 'recreation-bases', icon: '🌲', type: 'booking'   },
            { name: 'Загородные дома',       slug: 'country-houses',   icon: '🏕️', type: 'booking'   },
            { name: 'Авиабилеты',            slug: 'flights',          icon: '✈️', type: 'booking'   },
            { name: 'Туры',                  slug: 'tours',            icon: '🗺️', type: 'booking'   },
            { name: 'Экскурсии',             slug: 'excursions',       icon: '🎒', type: 'booking'   },
            { name: 'Визы',                  slug: 'visas',            icon: '📋', type: 'service'   },
            { name: 'Трансферы',             slug: 'transfers',        icon: '🚐', type: 'transport' },
            { name: 'Круизы',               slug: 'cruises',          icon: '🚢', type: 'booking'   },
            { name: 'ЖД-билеты',            slug: 'trains',           icon: '🚆', type: 'booking'   },
            { name: 'Страховки путешествий', slug: 'travel-insurance', icon: '🛡️', type: 'service'   },
        ],
    },
    {
        id: 'education',
        name: 'Образование',
        icon: '📚',
        desc: 'Курсы, репетиторы, языки, книги',
        count: 156,
        verticals: [
            { name: 'Онлайн-курсы',      slug: 'courses',        icon: '🎓', type: 'service' },
            { name: 'Репетиторы',         slug: 'tutors',         icon: '👨‍🏫', type: 'service' },
            { name: 'Языковые школы',     slug: 'languages',      icon: '🌍', type: 'service' },
            { name: 'Книги и литература', slug: 'books',          icon: '📖', type: 'product' },
            { name: 'IT-курсы',          slug: 'it-courses',     icon: '💻', type: 'service' },
            { name: 'MBA и бизнес',      slug: 'mba',            icon: '📈', type: 'service' },
            { name: 'Детские кружки',    slug: 'kids-clubs',     icon: '🧒', type: 'service' },
            { name: 'Музыкальные школы', slug: 'music-school',   icon: '🎵', type: 'service' },
            { name: 'Автошколы',         slug: 'driving-school', icon: '🚗', type: 'service' },
            { name: 'Консалтинг',        slug: 'consulting',     icon: '💼', type: 'service' },
        ],
    },
    {
        id: 'creative',
        name: 'Творчество',
        icon: '🎨',
        desc: 'Арт, фото, музыка, рукоделие',
        count: 98,
        verticals: [
            { name: 'Художники',            slug: 'artists',      icon: '🎨', type: 'service' },
            { name: 'Фотографы',            slug: 'photography',  icon: '📸', type: 'service' },
            { name: 'Музыка и инструменты', slug: 'music',        icon: '🎸', type: 'product' },
            { name: 'Рукоделие',            slug: 'craft',        icon: '✂️', type: 'product' },
            { name: 'Коллекционные товары', slug: 'collectibles', icon: '🏆', type: 'product' },
            { name: 'Видеографы',           slug: 'video',        icon: '🎬', type: 'service' },
            { name: 'Дизайнеры',            slug: 'designers',    icon: '🖥️', type: 'service' },
            { name: 'Граверы',              slug: 'engraving',    icon: '🔱', type: 'service' },
            { name: 'Каллиграфия',          slug: 'calligraphy',  icon: '🖊️', type: 'service' },
            { name: 'Керамика',             slug: 'ceramics',     icon: '🏺', type: 'product' },
        ],
    },
    {
        id: 'sports',
        name: 'Спорт и развлечения',
        icon: '⚽',
        desc: 'Залы, мероприятия, билеты, праздники',
        count: 134,
        verticals: [
            { name: 'Спортзалы',          slug: 'sports-gyms', icon: '🏋️', type: 'service' },
            { name: 'Мероприятия',        slug: 'events',      icon: '🎪', type: 'event'   },
            { name: 'Свадьбы',            slug: 'weddings',    icon: '💒', type: 'event'   },
            { name: 'Праздники',          slug: 'parties',     icon: '🎉', type: 'event'   },
            { name: 'Квесты',            slug: 'quests',      icon: '🔍', type: 'booking' },
            { name: 'Билеты',            slug: 'tickets',     icon: '🎫', type: 'booking' },
            { name: 'Кинотеатры',        slug: 'cinema',      icon: '🎬', type: 'booking' },
            { name: 'Парки развлечений',  slug: 'amusement',   icon: '🎡', type: 'booking' },
            { name: 'Игрушки и игры',    slug: 'toys',        icon: '🧸', type: 'product' },
            { name: 'Боулинг и бильярд', slug: 'bowling',     icon: '🎳', type: 'booking' },
        ],
    },
    {
        id: 'pets',
        name: 'Питомцы',
        icon: '🐾',
        desc: 'Ветклиники, корма, груминг, дрессировка',
        count: 87,
        verticals: [
            { name: 'Ветклиники',             slug: 'vet-clinics',     icon: '🩺', type: 'service' },
            { name: 'Груминг',                 slug: 'grooming',        icon: '🐩', type: 'service' },
            { name: 'Корма',                   slug: 'pet-food',        icon: '🦴', type: 'product' },
            { name: 'Аксессуары для питомцев', slug: 'pet-accessories', icon: '🎀', type: 'product' },
            { name: 'Дрессировка',             slug: 'training',        icon: '🐕', type: 'service' },
            { name: 'Гостиницы для животных',  slug: 'pet-hotels',      icon: '🏠', type: 'booking' },
            { name: 'Выгул',                   slug: 'dog-walking',     icon: '🦮', type: 'service' },
            { name: 'Зоомагазины',             slug: 'pet-shops',       icon: '🐠', type: 'product' },
            { name: 'Аквариумистика',          slug: 'aquariums',       icon: '🐟', type: 'product' },
            { name: 'Разведение',              slug: 'breeding',        icon: '🐈', type: 'service' },
        ],
    },
    {
        id: 'business',
        name: 'Бизнес и услуги',
        icon: '💼',
        desc: 'Недвижимость, юристы, страхование, IT',
        count: 203,
        verticals: [
            { name: 'Недвижимость',        slug: 'realestate',   icon: '🏠', type: 'booking' },
            { name: 'Юристы',              slug: 'legal',        icon: '⚖️', type: 'service' },
            { name: 'Страхование',         slug: 'insurance',    icon: '🛡️', type: 'service' },
            { name: 'Бухгалтерия',         slug: 'accounting',   icon: '📊', type: 'service' },
            { name: 'Фриланс',            slug: 'freelance',    icon: '💻', type: 'service' },
            { name: 'Электроника',         slug: 'electronics',  icon: '📱', type: 'product' },
            { name: 'Печать и полиграфия', slug: 'printing',     icon: '🖨️', type: 'service' },
            { name: 'Переводы',            slug: 'translation',  icon: '🌐', type: 'service' },
            { name: 'HR и рекрутинг',      slug: 'hr',           icon: '👥', type: 'service' },
            { name: 'Саморазвитие',        slug: 'personal-dev', icon: '🧠', type: 'service' },
        ],
    },
];

/** Общее количество вертикалей */
export const totalVerticals = megaCategories.reduce(
    (sum, cat) => sum + cat.verticals.length, 0
);

/** Плоский массив всех вертикалей (с categoryId, categoryName, type) */
export const allVerticals = megaCategories.flatMap((cat) =>
    cat.verticals.map((v) => ({ ...v, categoryId: cat.id, categoryName: cat.name }))
);

/** Поиск категории по slug вертикали */
export function findBySlug(slug) {
    for (const cat of megaCategories) {
        const v = cat.verticals.find((v) => v.slug === slug);
        if (v) return { category: cat, vertical: v };
    }
    return null;
}

/** Поиск мега-категории по id */
export function findCategory(id) {
    return megaCategories.find((c) => c.id === id) || null;
}

/* ── Демо-изображения по типу ── */
const demoImages = {
    service: [
        'https://images.unsplash.com/photo-1560066984-138dadb4c035?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1519824145371-296894a0daa9?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1552693673-1bf958298935?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1600334089648-b0d9d3028eb2?q=80&w=400&auto=format&fit=crop',
    ],
    product: [
        'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1560343090-f0409e92791a?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1491553895911-0055eca6402d?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=400&auto=format&fit=crop',
    ],
    transport: [
        'https://images.unsplash.com/photo-1549317661-bd32c8ce0afa?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1511527661048-7fe73d85e9a4?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1517524206127-48bf692d6e01?q=80&w=400&auto=format&fit=crop',
    ],
    booking: [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1455587734955-081b22074882?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1496417263034-38ec4f0b665a?q=80&w=400&auto=format&fit=crop',
    ],
    food: [
        'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?q=80&w=400&auto=format&fit=crop',
    ],
    event: [
        'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=400&auto=format&fit=crop',
    ],
};

/** Генератор демо-карточек для вертикали (type-aware) */
export function generateDemoItems(vertical, count = 6) {
    const t = vertical.type || 'product';
    const imgs = demoImages[t] || demoImages.product;

    const templates = {
        product: (i) => ({
            id: i + 1,
            name: [`Бестселлер «${vertical.name}»`, 'Новинка сезона', `${vertical.name} — Премиум`, 'Хит продаж', 'Выбор AI', 'Топ-рейтинг'][i],
            price: [2500, 3800, 1200, 4500, 1900, 6200][i],
            rating: [4.8, 4.9, 4.6, 4.7, 5.0, 4.5][i],
            subtitle: `⭐ ${[4.8, 4.9, 4.6, 4.7, 5.0, 4.5][i]} · ${[234, 89, 512, 45, 178, 67][i]} отзывов`,
            image: imgs[i % imgs.length],
            category: vertical.name,
        }),
        service: (i) => ({
            id: i + 1,
            name: [`${vertical.name} — Премиум`, 'Мастер-класс', 'VIP-обслуживание', 'Стандарт', 'Экспресс', 'Пакет «Всё включено»'][i],
            price: [2500, 1800, 4500, 1200, 800, 5900][i],
            rating: [4.9, 4.7, 5.0, 4.5, 4.8, 4.6][i],
            subtitle: `⭐ ${[4.9, 4.7, 5.0, 4.5, 4.8, 4.6][i]} · ${[60, 45, 90, 30, 20, 120][i]} мин`,
            image: imgs[i % imgs.length],
            category: vertical.name,
        }),
        transport: (i) => ({
            id: i + 1,
            name: ['Эконом', 'Комфорт', 'Бизнес', 'Премиум', 'Минивэн', 'Грузовой'][i],
            price: [299, 499, 899, 1500, 699, 1200][i],
            rating: [4.7, 4.8, 4.9, 5.0, 4.6, 4.5][i],
            subtitle: `🚗 ${[3, 5, 7, 10, 4, 8][i]} мин`,
            image: imgs[i % imgs.length],
            category: vertical.name,
        }),
        booking: (i) => {
            const accommodationTypes = [
                { type: 'hotel',      label: 'Гостиница',       stars: [3, 4, 5, 4, 5, 3][i] },
                { type: 'hostel',     label: 'Хостел',          stars: 0 },
                { type: 'apartment',  label: 'Апартаменты',     stars: 0 },
                { type: 'resort',     label: 'Пансионат',       stars: [3, 4, 3, 4, 3, 4][i] },
                { type: 'recreation', label: 'База отдыха',     stars: 0 },
                { type: 'cottage',    label: 'Загородный дом',  stars: 0 },
            ];
            const accom = accommodationTypes[i % accommodationTypes.length];

            const propertyNames = [
                [`Отель «Империал»`, `Гранд Отель «Русь»`, `Park Hotel`, `Бутик-отель «Арт»`, `Отель «Панорама»`, `Гостиница «Центральная»`],
                [`Хостел «Дружба»`, `LoftHouse`, `HiHostel`, `FriendlyStay`, `BackPacker Hub`, `Хостел «Странник»`],
                [`Апарты «Сити»`, `Lux Apartments`, `ComfortFlat`, `Студия «Минимал»`, `Апартаменты «Вид»`, `SmartApart`],
                [`Пансионат «Лесной»`, `Пансионат «Берег»`, `Здравница «Алтай»`, `Пансионат «Заря»`, `Курорт «Южный»`, `Санаторий «Дубки»`],
                [`База «Лесная поляна»`, `Кемпинг «Озеро»`, `База «Рыбацкая»`, `Глэмпинг «Сосны»`, `Эко-база «Тихий»`, `Турбаза «Горная»`],
                [`Коттедж «Уют»`, `Дача «Берёзка»`, `Шале «Альпийское»`, `Дом «У реки»`, `Вилла «Солнечная»`, `Дом «Лесной»`],
            ];

            const allRooms = [
                [ // hotel rooms
                    { name: 'Стандарт',     pricePerNight: 3200, capacity: 2, area: 22, available: 5 },
                    { name: 'Улучшенный',   pricePerNight: 4800, capacity: 2, area: 28, available: 3 },
                    { name: 'Полулюкс',     pricePerNight: 6500, capacity: 3, area: 35, available: 2 },
                    { name: 'Люкс',         pricePerNight: 12000, capacity: 2, area: 50, available: 1 },
                    { name: 'Семейный',     pricePerNight: 7800, capacity: 4, area: 42, available: 2 },
                ],
                [ // hostel rooms
                    { name: 'Место в 6-мест.',  pricePerNight: 600, capacity: 1, area: 4, available: 12 },
                    { name: 'Место в 4-мест.',  pricePerNight: 800, capacity: 1, area: 5, available: 8 },
                    { name: 'Двухместный',       pricePerNight: 1800, capacity: 2, area: 14, available: 3 },
                    { name: 'Приватная комната', pricePerNight: 2400, capacity: 2, area: 16, available: 2 },
                ],
                [ // apartment
                    { name: 'Студия',          pricePerNight: 2800, capacity: 2, area: 30, available: 4 },
                    { name: '1-комнатная',     pricePerNight: 3500, capacity: 3, area: 40, available: 3 },
                    { name: '2-комнатная',     pricePerNight: 5200, capacity: 4, area: 60, available: 2 },
                    { name: '3-комнатная',     pricePerNight: 7500, capacity: 6, area: 85, available: 1 },
                ],
                [ // resort/boarding house
                    { name: 'Стандарт',         pricePerNight: 3800, capacity: 2, area: 24, available: 8 },
                    { name: 'Стандарт+',        pricePerNight: 4500, capacity: 2, area: 28, available: 5 },
                    { name: 'Комфорт',          pricePerNight: 5800, capacity: 3, area: 35, available: 3 },
                    { name: 'Люкс',             pricePerNight: 9500, capacity: 2, area: 48, available: 1 },
                ],
                [ // recreation base
                    { name: 'Эконом-домик',    pricePerNight: 2200, capacity: 4, area: 25, available: 6 },
                    { name: 'Стандарт-домик',  pricePerNight: 3600, capacity: 4, area: 35, available: 4 },
                    { name: 'Комфорт-домик',   pricePerNight: 5500, capacity: 6, area: 50, available: 2 },
                    { name: 'VIP-коттедж',     pricePerNight: 12000, capacity: 8, area: 100, available: 1 },
                ],
                [ // cottage/country house
                    { name: 'Дом (до 4 гостей)',  pricePerNight: 5000, capacity: 4, area: 60, available: 3 },
                    { name: 'Дом (до 6 гостей)',  pricePerNight: 7500, capacity: 6, area: 90, available: 2 },
                    { name: 'Дом (до 10 гостей)', pricePerNight: 12000, capacity: 10, area: 140, available: 1 },
                    { name: 'Вилла с бассейном',  pricePerNight: 20000, capacity: 12, area: 200, available: 1 },
                ],
            ];

            const amenitySets = [
                ['Wi-Fi', 'Кондиционер', 'Парковка', 'Ресторан', 'Бассейн', 'Фитнес'],
                ['Wi-Fi', 'Кухня', 'Стиральная м.', 'Камера хранения', 'Общая зона'],
                ['Wi-Fi', 'Кухня', 'Стиральная м.', 'Кондиционер', 'Парковка', 'Балкон'],
                ['Wi-Fi', 'Питание', 'Бассейн', 'SPA', 'Парковка', 'Мед. центр'],
                ['Wi-Fi', 'Мангал', 'Баня', 'Рыбалка', 'Парковка', 'Пляж'],
                ['Wi-Fi', 'Кухня', 'Баня', 'Мангал', 'Парковка', 'Детская площадка'],
            ];

            const amenityIcons = {
                'Wi-Fi': '📶', 'Кондиционер': '❄️', 'Парковка': '🅿️', 'Ресторан': '🍽️',
                'Бассейн': '🏊', 'Фитнес': '💪', 'Кухня': '🍳', 'Стиральная м.': '🧺',
                'Камера хранения': '🔒', 'Общая зона': '🛋️', 'Балкон': '🌇',
                'Питание': '🍽️', 'SPA': '💆', 'Мед. центр': '🏥',
                'Мангал': '🔥', 'Баня': '🧖', 'Рыбалка': '🎣', 'Пляж': '🏖️',
                'Детская площадка': '🧒',
            };

            const rooms = allRooms[i % allRooms.length];
            const minPrice = Math.min(...rooms.map(r => r.pricePerNight));
            const amenities = amenitySets[i % amenitySets.length];
            const totalRooms = rooms.reduce((s, r) => s + r.available, 0);

            return {
                id: i + 1,
                name: propertyNames[i % propertyNames.length][i % 6],
                price: minPrice,
                pricePerNight: minPrice,
                rating: [4.9, 4.3, 4.8, 4.5, 4.7, 4.6][i],
                subtitle: `${accom.label}${accom.stars ? ' ★'.repeat(accom.stars) : ''} · ${totalRooms} номеров`,
                image: imgs[i % imgs.length],
                category: vertical.name,
                accommodationType: accom.type,
                accommodationLabel: accom.label,
                hotelStars: accom.stars,
                rooms,
                amenities,
                amenityIcons,
                totalRooms,
                checkIn: '14:00',
                checkOut: '12:00',
            };
        },
        food: (i) => ({
            id: i + 1,
            name: [`${vertical.name} — Хит дня`, 'Шеф-рекомендация', 'Сет «Премиум»', 'Бизнес-ланч', 'Завтрак дня', 'Десерт'][i],
            price: [890, 1450, 2200, 590, 450, 350][i],
            rating: [4.9, 4.8, 4.7, 4.5, 4.6, 4.8][i],
            subtitle: `🛵 ${[25, 30, 35, 20, 15, 25][i]} мин · ${[320, 450, 680, 280, 220, 180][i]} ккал`,
            image: imgs[i % imgs.length],
            category: vertical.name,
        }),
        event: (i) => ({
            id: i + 1,
            name: [`${vertical.name} — VIP`, 'Стандарт', 'Детский', 'Корпоратив', 'Романтический', 'Фан-зона'][i],
            price: [3500, 1500, 800, 5000, 2500, 1200][i],
            rating: [4.9, 4.7, 4.8, 4.6, 5.0, 4.5][i],
            subtitle: `📅 ${['15 мая', '22 мая', '1 июня', '10 июня', '5 июля', '20 июля'][i]} · ${['19:00', '14:00', '12:00', '18:00', '20:00', '16:00'][i]}`,
            image: imgs[i % imgs.length],
            category: vertical.name,
        }),
    };

    /* ── Гео-данные: адреса, координаты, режим доставки ── */
    const demoAddresses = [
        'ул. Тверская, 12', 'Невский пр., 45', 'пр. Мира, 78', 'ул. Ленина, 23',
        'ул. Пушкина, 5', 'Красный пр., 100', 'ул. Арбат, 33', 'ул. Баумана, 19',
        'пр. Революции, 8', 'ул. Советская, 56', 'Литейный пр., 41', 'ул. Кирова, 15',
        'ул. Гагарина, 90', 'пр. Ленина, 62', 'ул. Московская, 7', 'ул. Садовая, 28',
        'Большая Морская, 3', 'ул. Восстания, 14', 'пр. Стачек, 67', 'ул. Маяковского, 11',
    ];

    // Режим доставки по типу вертикали
    const deliveryModes = {
        product:   'courier',   // 🚚 Курьерская доставка
        service:   'visit',     // 📍 Посещение (вы идёте к мастеру)
        transport: 'pickup',    // 🚗 Подача
        booking:   'visit',     // 📍 Посещение (отель, зал)
        food:      'courier',   // 🚚 Курьер еды
        event:     'visit',     // 📍 Место проведения
    };

    // Случайные координаты в радиусе ~10 км от центра (по умолчанию Москва)
    const centerLat = 55.7558;
    const centerLng = 37.6173;

    const gen = templates[t] || templates.product;
    return Array.from({ length: count }, (_, i) => {
        const base = gen(i % 6);
        const cycle = Math.floor(i / 6);
        // Разброс координат ±0.08° ≈ ±8 км
        const lat = centerLat + (Math.random() - 0.5) * 0.16;
        const lng = centerLng + (Math.random() - 0.5) * 0.16;
        return {
            ...base,
            id: i + 1,
            name: cycle > 0 ? `${base.name} #${cycle + 1}` : base.name,
            price: Math.round(base.price * (0.8 + Math.random() * 0.4)),
            rating: Math.round((4.0 + Math.random()) * 10) / 10,
            lat,
            lng,
            address: demoAddresses[i % demoAddresses.length],
            deliveryMode: deliveryModes[t] || 'courier',
        };
    });
}
