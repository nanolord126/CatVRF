import { ref, onMounted, type Ref } from 'vue';

/**
 * 5 цветовых схем «Экосистемы будущего»
 * По умолчанию — Мята (мягкий мятный фон)
 */

export interface ThemeOption {
    id: string;
    label: string;
    short: string;
}

export const THEMES: ThemeOption[] = [
    { id: 'mint',     label: '🌿 Мята',     short: '🌿' },
    { id: 'day',      label: '☀️ День',      short: '☀️' },
    { id: 'night',    label: '🌙 Ночь',     short: '🌙' },
    { id: 'sunset',   label: '🌅 Закат',    short: '🌅' },
    { id: 'lavender', label: '💜 Лаванда',  short: '💜' },
];

const THEME_IDS: string[] = THEMES.map((t) => t.id);

/* ── singleton reactive ref, shared across all components ── */
const currentTheme: Ref<string> = ref(
    (() => {
        try {
            const saved = localStorage.getItem('user-theme');
            return saved && THEME_IDS.includes(saved) ? saved : 'mint';
        } catch {
            return 'mint';
        }
    })()
);

/**
 * Основная функция применения темы:
 * 1. data-theme на <html>
 * 2. localStorage
 * 3. reactive ref
 * 4. CustomEvent для внешних подписчиков
 */
function applyTheme(themeId: string): void {
    if (!THEME_IDS.includes(themeId)) themeId = 'mint';

    try { document.documentElement.setAttribute('data-theme', themeId); } catch { /* SSR */ }
    try { localStorage.setItem('user-theme', themeId); } catch { /* private mode */ }

    currentTheme.value = themeId;

    try {
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: themeId } }));
    } catch { /* noop */ }
}

export interface UseThemeReturn {
    currentTheme: Ref<string>;
    themes: ThemeOption[];
    setTheme: (themeId: string) => void;
    nextTheme: () => void;
}

export function useTheme(): UseThemeReturn {
    onMounted(() => {
        const attr = document.documentElement.getAttribute('data-theme');
        if (attr !== currentTheme.value) {
            applyTheme(currentTheme.value);
        }
    });

    function setTheme(themeId: string): void {
        applyTheme(themeId);
    }

    function nextTheme(): void {
        const idx = THEME_IDS.indexOf(currentTheme.value);
        const next = THEMES[(idx + 1) % THEMES.length];
        applyTheme(next.id);
    }

    return { currentTheme, themes: THEMES, setTheme, nextTheme };
}
