/**
 * CatVRF 2026 — Pinia: useUI Store
 * Стор для UI-состояния: sidebar, modal, bottom sheet, fullscreen, breakpoints, theme
 */

import { defineStore } from 'pinia';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import type {
    ModalState,
    BottomSheetConfig,
    BottomSheetState,
    ModalSize,
    Toast,
    ToastType,
} from '@/types/ui';

export const useUI = defineStore('ui', () => {
    /* ── State ──────────────────────────────────────────────────────── */
    const sidebarCollapsed = ref(false);
    const sidebarMobileOpen = ref(false);
    const currentModal = ref<ModalState | null>(null);
    const bottomSheet = ref<BottomSheetConfig>({
        component: null,
        props: {},
        state: 'closed',
        snapPoints: [0.3, 0.6, 1],
    });
    const toasts = ref<Toast[]>([]);
    const isFullScreen = ref(false);
    const fullScreenComponent = ref<string | null>(null);
    const isMobile = ref(false);
    const isTablet = ref(false);
    const isDesktop = ref(true);
    const theme = ref<'light' | 'dark' | 'system'>('system');
    const locale = ref('ru');

    /* ── Getters ────────────────────────────────────────────────────── */
    const hasModal = computed(() => !!currentModal.value);
    const hasBottomSheet = computed(() => bottomSheet.value.state !== 'closed');
    const toastCount = computed(() => toasts.value.length);

    /* ── Sidebar Actions ────────────────────────────────────────────── */
    function toggleSidebar(): void {
        if (isMobile.value) {
            sidebarMobileOpen.value = !sidebarMobileOpen.value;
        } else {
            sidebarCollapsed.value = !sidebarCollapsed.value;
        }
    }

    function closeMobileSidebar(): void {
        sidebarMobileOpen.value = false;
    }

    /* ── Modal Actions ──────────────────────────────────────────────── */
    function openModal(component: string, props: Record<string, unknown> = {}, size: ModalSize = 'md', closable = true): void {
        currentModal.value = { component, props, size, closable };
    }

    function closeModal(): void {
        currentModal.value = null;
    }

    /* ── Bottom Sheet Actions ───────────────────────────────────────── */
    function openBottomSheet(component: string, props: Record<string, unknown> = {}, state: BottomSheetState = 'half'): void {
        bottomSheet.value = {
            component,
            props,
            state,
            snapPoints: [0.3, 0.6, 1],
        };
    }

    function setBottomSheetState(state: BottomSheetState): void {
        bottomSheet.value.state = state;
    }

    function closeBottomSheet(): void {
        bottomSheet.value = {
            component: null,
            props: {},
            state: 'closed',
            snapPoints: [0.3, 0.6, 1],
        };
    }

    /* ── Toast Actions ──────────────────────────────────────────────── */
    function addToast(type: ToastType, title: string, message: string, duration = 4000): void {
        const id = crypto.randomUUID();
        toasts.value.push({ id, type, title, message, duration, createdAt: Date.now() });

        if (duration > 0) {
            setTimeout(() => removeToast(id), duration);
        }
    }

    function removeToast(id: string): void {
        const index = toasts.value.findIndex((t: Toast) => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    }

    /* ── FullScreen Actions ─────────────────────────────────────────── */
    function enterFullScreen(component: string): void {
        isFullScreen.value = true;
        fullScreenComponent.value = component;
    }

    function exitFullScreen(): void {
        isFullScreen.value = false;
        fullScreenComponent.value = null;
    }

    /* ── Theme ──────────────────────────────────────────────────────── */
    function setTheme(t: 'light' | 'dark' | 'system'): void {
        theme.value = t;
        if (t === 'system') {
            const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', isDark);
        } else {
            document.documentElement.classList.toggle('dark', t === 'dark');
        }
        localStorage.setItem('theme', t);
    }

    /* ── Breakpoints (SSR-safe) ─────────────────────────────────────── */
    let resizeHandler: (() => void) | null = null;

    function updateBreakpoints(): void {
        if (typeof window === 'undefined') return;
        const w = window.innerWidth;
        isMobile.value = w < 768;
        isTablet.value = w >= 768 && w < 1024;
        isDesktop.value = w >= 1024;
    }

    function initBreakpoints(): void {
        updateBreakpoints();
        resizeHandler = updateBreakpoints;
        window.addEventListener('resize', resizeHandler);
    }

    function destroyBreakpoints(): void {
        if (resizeHandler) {
            window.removeEventListener('resize', resizeHandler);
            resizeHandler = null;
        }
    }

    /* ── Listen for global toast CustomEvents ───────────────────────── */
    function initGlobalToastListener(): void {
        window.addEventListener('toast', ((e: CustomEvent) => {
            const { type, title, message, duration } = e.detail;
            addToast(type, title, message, duration);
        }) as EventListener);
    }

    return {
        /* state */
        sidebarCollapsed,
        sidebarMobileOpen,
        currentModal,
        bottomSheet,
        toasts,
        isFullScreen,
        fullScreenComponent,
        isMobile,
        isTablet,
        isDesktop,
        theme,
        locale,
        /* getters */
        hasModal,
        hasBottomSheet,
        toastCount,
        /* actions */
        toggleSidebar,
        closeMobileSidebar,
        openModal,
        closeModal,
        openBottomSheet,
        setBottomSheetState,
        closeBottomSheet,
        addToast,
        removeToast,
        enterFullScreen,
        exitFullScreen,
        setTheme,
        initBreakpoints,
        destroyBreakpoints,
        initGlobalToastListener,
    };
}, {
    persist: {
        pick: ['sidebarCollapsed', 'theme', 'locale'],
    },
});
