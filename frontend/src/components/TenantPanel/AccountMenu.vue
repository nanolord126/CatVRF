<template>
  <div class="account-menu">
    <button class="account-button" @click="toggleMenu">
      <div class="account-avatar">
        <User :size="20" />
      </div>
      <ChevronDown :size="16" class="dropdown-icon" :class="{ open: isOpen }" />
    </button>

    <div v-if="isOpen" class="menu-dropdown">
      <div class="menu-header">
        <div class="user-info">
          <div class="user-name">{{ user.name }}</div>
          <div class="user-email">{{ user.email }}</div>
        </div>
        <div class="personal-balance">
          <span class="balance-label">{{ $t('wallet.personalBalance') }}:</span>
          <span class="balance-amount">{{ formatCurrency(personalBalance) }}</span>
        </div>
      </div>

      <div class="menu-items">
        <button class="menu-item" @click="$emit('profile')">
          <User :size="16" />
          <span>{{ $t('account.profile') }}</span>
        </button>
        <button class="menu-item" @click="$emit('settings')">
          <Settings :size="16" />
          <span>{{ $t('account.settings') }}</span>
        </button>
        <div class="menu-divider"></div>
        <button class="menu-item logout" @click="$emit('logout')">
          <LogOut :size="16" />
          <span>{{ $t('account.logout') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { User, ChevronDown, Settings, LogOut } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
  user: any;
  personalBalance: number;
}>();

defineEmits<{
  profile: [];
  settings: [];
  logout: [];
}>();

const isOpen = ref(false);

const toggleMenu = () => {
  isOpen.value = !isOpen.value;
};

const closeMenu = () => {
  isOpen.value = false;
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0,
  }).format(amount);
};

onMounted(() => {
  document.addEventListener('click', closeMenu);
});

onUnmounted(() => {
  document.removeEventListener('click', closeMenu);
});
</script>

<style scoped>
.account-menu {
  position: relative;
}

.account-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
  cursor: pointer;
  color: var(--text-primary);
}

.account-button:hover {
  background: var(--bg-tertiary);
}

.account-avatar {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
  border-radius: 50%;
}

.dropdown-icon {
  color: var(--text-secondary);
  transition: transform 0.2s;
}

.dropdown-icon.open {
  transform: rotate(180deg);
}

.menu-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: 300px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}

.menu-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--border-color);
}

.user-info {
  margin-bottom: 1rem;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
}

.user-email {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.personal-balance {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--bg-tertiary);
  border-radius: 0.5rem;
}

.balance-label {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.balance-amount {
  font-weight: 700;
  color: var(--primary-color);
}

.menu-items {
  padding: 0.5rem;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  color: var(--text-primary);
  font-size: 0.875rem;
  transition: all 0.2s;
}

.menu-item:hover {
  background: var(--bg-tertiary);
}

.menu-item.logout {
  color: #F87171;
}

.menu-item.logout:hover {
  background: #FEF2F2;
}

.menu-divider {
  height: 1px;
  background: var(--border-color);
  margin: 0.5rem 0;
}
</style>
