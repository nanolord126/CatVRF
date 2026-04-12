/**
 * CatVRF 2026 — UI Types
 * Типы для UI-компонентов, модалок, тостов, full-screen
 */

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | 'full';

export interface ModalState {
    component: string | null;
    props: Record<string, unknown>;
    size: ModalSize;
    closable: boolean;
}

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: string;
    type: ToastType;
    title: string;
    message: string;
    duration: number;
    createdAt: number;
}

export type BottomSheetState = 'closed' | 'peek' | 'half' | 'full';

export interface BottomSheetConfig {
    component: string | null;
    props: Record<string, unknown>;
    state: BottomSheetState;
    snapPoints: number[];
}

export interface UIState {
    sidebarCollapsed: boolean;
    sidebarMobileOpen: boolean;
    currentModal: ModalState | null;
    bottomSheet: BottomSheetConfig;
    toasts: Toast[];
    isFullScreen: boolean;
    fullScreenComponent: string | null;
    isMobile: boolean;
    isTablet: boolean;
    isDesktop: boolean;
    theme: 'light' | 'dark' | 'system';
    locale: string;
}

export interface Breadcrumb {
    label: string;
    href?: string;
    icon?: string;
}

export interface SidebarItem {
    id: string;
    label: string;
    icon: string;
    href?: string;
    badge?: number;
    children?: SidebarItem[];
    permission?: string;
}
