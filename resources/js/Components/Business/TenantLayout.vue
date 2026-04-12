<template>
  <div
    :class="[
      'min-h-screen bg-linear-to-br from-neutral-950/95 to-neutral-900/90 text-white font-sans',
      'flex flex-col',
      { 'overflow-hidden': uiStore.isLoading },
      { 'fullscreen': isFullscreen }
    ]"
  >
    <!-- Глобальный loading overlay -->
    <transition name="fade">
      <div
        v-if="uiStore.isLoading"
        class="fixed z-99 inset-0 flex items-center justify-center bg-neutral-950/80 backdrop-blur-md"
      >
        <svg class="animate-spin w-9 h-9 text-primary-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
        </svg>
      </div>
    </transition>

    <!-- Header (desktop/tablet, скрыт в fullscreen) -->
    <TenantHeader v-if="!isFullscreen" class="fixed z-90 insetBlockStart-0 insetInlineStart-0 w-full" />

    <div
      :class="[
        'flex flex-1 w-full',
        'pt-16', // Header height
        { 'pt-0': isFullscreen }
      ]"
    >
      <!-- Sidebar (desktop/tablet, скрыт на мобиле) -->
      <TenantSidebar
        v-if="!isMobile"
        :collapsed="sidebarCollapsed"
        @toggle-collapse="toggleSidebar"
        class="z-80 h-full min-w-18 max-w-64 transition-all duration-300"
      />

      <!-- Main content -->
      <main
        :class="[
          'flex-1 flex flex-col relative',
          'bg-glassmorphic/80 rounded-xl shadow-xl mx-2 my-3 p-4',
          { 'mx-0 my-0 p-0 rounded-none shadow-none bg-transparent': isFullscreen },
          { 'transition-all duration-300': !isFullscreen }
        ]"
      >
        <slot />
        <!-- Optional right panel (например, detail drawer) -->
        <slot name="right-panel" />
      </main>
    </div>

    <!-- BottomNavigation (только на мобиле, скрыт в fullscreen) -->
    <BottomNavigation v-if="isMobile && !isFullscreen" class="fixed z-90 insetBlockEnd-0 insetInlineStart-0 w-full" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useUIStore } from '@/Stores/useUIStore';
import { useTenantStore } from '@/Stores/useTenantStore';
import TenantHeader from './TenantHeader.vue';
import TenantSidebar from './TenantSidebar.vue';
import BottomNavigation from './BottomNavigation.vue';

// Импорт всех страниц для Inertia (используются через <slot />)
import TenantDashboard from './TenantDashboard.vue';
import TenantCalendar from './TenantCalendar.vue';
import TenantClientList from './TenantClientList.vue';
import TenantFinance from './TenantFinance.vue';
import TenantStaff from './TenantStaff.vue';
import TenantBookings from './TenantBookings.vue';
import TenantMarketing from './TenantMarketing.vue';
import TenantSettings from './TenantSettings.vue';
import TenantHelp from './TenantHelp.vue';
import TenantPayroll from './TenantPayroll.vue';
import TenantInventory from './TenantInventory.vue';
import TenantCRM from './TenantCRM.vue';
import TenantMailings from './TenantMailings.vue';
import TenantCatalog from './TenantCatalog.vue';
import TenantAnalytics from './TenantAnalytics.vue';
import TenantReports from './TenantReports.vue';
import TenantPublic from './TenantPublic.vue';
import TenantNotifications from './TenantNotifications.vue';
import TenantBranches from './TenantBranches.vue';
import TenantPayments from './TenantPayments.vue';
import TenantLoyalty from './TenantLoyalty.vue';
import TenantAds from './TenantAds.vue';
import TenantIntegrations from './TenantIntegrations.vue';
import TenantSEO from './TenantSEO.vue';

const uiStore = useUIStore();
const tenantStore = useTenantStore();

const isMobile = computed(() => uiStore.isMobile);
const isFullscreen = computed(() => uiStore.isFullscreen);
const sidebarCollapsed = ref(false);

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value;
}

onMounted(() => {
  // Применить tenant context (B2B)
  tenantStore.ensureTenantContext();
});
</script>

<style scoped>
.bg-glassmorphic {
  background: linear-gradient(135deg, rgba(36,37,46,0.85) 0%, rgba(44,47,59,0.80) 100%);
  backdrop-filter: blur(16px) saturate(180%);
  box-shadow: 0 2px 24px 0 rgba(0,0,0,0.18);
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.25s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.fullscreen {
  min-block-size: 100vh;
  background: #18181c;
}
</style>
