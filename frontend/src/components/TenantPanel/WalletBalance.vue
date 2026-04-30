<template>
  <div class="wallet-balance" @click="$emit('click')">
    <div class="balance-icon">
      <Wallet :size="20" />
    </div>
    <div class="balance-info">
      <div class="balance-amount">{{ formatCurrency(balance) }}</div>
      <div class="balance-label">{{ $t('wallet.balance') }}</div>
    </div>
    <ChevronDown :size="16" class="dropdown-icon" />
  </div>
</template>

<script setup lang="ts">
import { Wallet, ChevronDown } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
  balance: number;
  currency: string;
}>();

defineEmits<{
  click: [];
}>();

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0,
  }).format(amount);
};
</script>

<style scoped>
.wallet-balance {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.wallet-balance:hover {
  background: var(--bg-tertiary);
  border-color: var(--primary-color);
}

.balance-icon {
  color: var(--primary-color);
}

.balance-info {
  display: flex;
  flex-direction: column;
}

.balance-amount {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-primary);
}

.balance-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.dropdown-icon {
  color: var(--text-secondary);
}
</style>
