<template>
  <div class="tenant-panel-crm">
    <div class="crm-header">
      <div class="header-left">
        <h1 class="crm-title">{{ $t('crm.title') }}</h1>
        <div class="tenant-info">
          <span class="tenant-name">{{ currentTenant.name }}</span>
          <span class="tenant-id">{{ currentTenant.id }}</span>
        </div>
      </div>

      <div class="header-right">
        <WalletBalance
          :balance="walletBalance"
          :currency="walletCurrency"
          @click="openWalletModal"
        />

        <ThemeSwitcher v-model="currentTheme" @change="handleThemeChange" />

        <BranchSwitcher
          :branches="branches"
          :current-branch="currentBranch"
          @change="handleBranchChange"
        />

        <AccountMenu
          :user="currentUser"
          :personal-balance="personalBalance"
          @profile="openProfile"
          @settings="openSettings"
          @logout="handleLogout"
        />
      </div>
    </div>

    <div class="crm-navigation">
      <div
        v-for="section in sections"
        :key="section.id"
        class="nav-item"
        :class="{ active: activeSection === section.id }"
        @click="setActiveSection(section.id)"
      >
        <component :is="section.icon" class="nav-icon" />
        <span class="nav-label">{{ $t(section.label) }}</span>
        <span v-if="section.badge" class="nav-badge">{{ section.badge }}</span>
      </div>
    </div>

    <div class="crm-content">
      <component
        :is="activeComponent"
        v-if="activeComponent"
        :tenant-id="currentTenant.id"
        :branch-id="currentBranch?.id"
        :key="activeSection"
      />
    </div>

    <WalletModal
      v-if="showWalletModal"
      :tenant-id="currentTenant.id"
      @close="showWalletModal = false"
    />

    <ProfileModal
      v-if="showProfileModal"
      :user="currentUser"
      @close="showProfileModal = false"
    />

    <SettingsModal
      v-if="showSettingsModal"
      :tenant-id="currentTenant.id"
      @close="showSettingsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  ShoppingCart,
  Users,
  Package,
  Warehouse,
  DollarSign,
  UserCheck,
  BarChart3,
  Megaphone,
  Settings,
  FileText,
  Globe,
} from 'lucide-vue-next';

import WalletBalance from './WalletBalance.vue';
import ThemeSwitcher from './ThemeSwitcher.vue';
import BranchSwitcher from './BranchSwitcher.vue';
import AccountMenu from './AccountMenu.vue';
import WalletModal from './WalletModal.vue';
import ProfileModal from './ProfileModal.vue';
import SettingsModal from './SettingsModal.vue';

import OrdersSection from './sections/OrdersSection.vue';
import StaffSection from './sections/StaffSection.vue';
import InventorySection from './sections/InventorySection.vue';
import WarehouseSection from './sections/WarehouseSection.vue';
import PayrollSection from './sections/PayrollSection.vue';
import HRSection from './sections/HRSection.vue';
import AnalyticsSection from './sections/AnalyticsSection.vue';
import MarketingSection from './sections/MarketingSection.vue';
import TenantSettingsSection from './sections/TenantSettingsSection.vue';
import DocumentsSection from './sections/DocumentsSection.vue';
import PublicSection from './sections/PublicSection.vue';

const { t } = useI18n();

const currentTenant = ref({ id: '', name: '' });
const currentUser = ref({ id: '', name: '', email: '' });
const currentBranch = ref(null);
const branches = ref([]);
const walletBalance = ref(0);
const walletCurrency = ref('RUB');
const personalBalance = ref(0);
const currentTheme = ref('light');
const activeSection = ref('orders');
const showWalletModal = ref(false);
const showProfileModal = ref(false);
const showSettingsModal = ref(false);

const sections = computed(() => [
  {
    id: 'orders',
    label: 'crm.sections.orders',
    icon: ShoppingCart,
    component: OrdersSection,
  },
  {
    id: 'staff',
    label: 'crm.sections.staff',
    icon: Users,
    component: StaffSection,
  },
  {
    id: 'inventory',
    label: 'crm.sections.inventory',
    icon: Package,
    component: InventorySection,
  },
  {
    id: 'warehouse',
    label: 'crm.sections.warehouse',
    icon: Warehouse,
    component: WarehouseSection,
  },
  {
    id: 'payroll',
    label: 'crm.sections.payroll',
    icon: DollarSign,
    component: PayrollSection,
  },
  {
    id: 'hr',
    label: 'crm.sections.hr',
    icon: UserCheck,
    component: HRSection,
  },
  {
    id: 'analytics',
    label: 'crm.sections.analytics',
    icon: BarChart3,
    component: AnalyticsSection,
  },
  {
    id: 'marketing',
    label: 'crm.sections.marketing',
    icon: Megaphone,
    component: MarketingSection,
  },
  {
    id: 'settings',
    label: 'crm.sections.settings',
    icon: Settings,
    component: TenantSettingsSection,
  },
  {
    id: 'documents',
    label: 'crm.sections.documents',
    icon: FileText,
    component: DocumentsSection,
  },
  {
    id: 'public',
    label: 'crm.sections.public',
    icon: Globe,
    component: PublicSection,
  },
]);

const activeComponent = computed(() => {
  const section = sections.value.find(s => s.id === activeSection.value);
  return section?.component || null;
});

const setActiveSection = (sectionId: string) => {
  activeSection.value = sectionId;
};

const openWalletModal = () => {
  showWalletModal.value = true;
};

const openProfile = () => {
  showProfileModal.value = true;
};

const openSettings = () => {
  showSettingsModal.value = true;
};

const handleThemeChange = (theme: string) => {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('theme', theme);
};

const handleBranchChange = (branch: any) => {
  currentBranch.value = branch;
  localStorage.setItem('currentBranch', JSON.stringify(branch));
};

const handleLogout = async () => {
  await fetch('/api/auth/logout', { method: 'POST' });
  window.location.href = '/login';
};

const loadData = async () => {
  try {
    const [tenantRes, userRes, branchesRes, walletRes] = await Promise.all([
      fetch('/api/tenant/current'),
      fetch('/api/user/current'),
      fetch('/api/tenant/branches'),
      fetch('/api/wallet/balance'),
    ]);

    currentTenant.value = await tenantRes.json();
    currentUser.value = await userRes.json();
    branches.value = await branchesRes.json();
    const walletData = await walletRes.json();
    walletBalance.value = walletData.balance;
    walletCurrency.value = walletData.currency;
    personalBalance.value = walletData.personal_balance;

    const savedBranch = localStorage.getItem('currentBranch');
    if (savedBranch) {
      currentBranch.value = JSON.parse(savedBranch);
    } else if (branches.value.length > 0) {
      currentBranch.value = branches.value[0];
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    currentTheme.value = savedTheme;
    document.documentElement.setAttribute('data-theme', savedTheme);
  } catch (error) {
    console.error('Failed to load CRM data:', error);
  }
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
.tenant-panel-crm {
  min-height: 100vh;
  background: var(--bg-primary);
  color: var(--text-primary);
}

.crm-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.crm-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  color: var(--text-primary);
}

.tenant-info {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.crm-navigation {
  display: flex;
  gap: 0.5rem;
  padding: 1rem 2rem;
  background: var(--bg-primary);
  border-bottom: 1px solid var(--border-color);
  overflow-x: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.nav-item:hover {
  background: var(--bg-tertiary);
  border-color: var(--primary-color);
}

.nav-item.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.nav-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.nav-label {
  font-size: 0.875rem;
  font-weight: 500;
}

.nav-badge {
  background: var(--danger-color);
  color: white;
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-weight: 600;
}

.crm-content {
  padding: 2rem;
}

@media (max-width: 768px) {
  .crm-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .crm-navigation {
    padding: 0.75rem 1rem;
  }

  .crm-content {
    padding: 1rem;
  }
}
</style>
