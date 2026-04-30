<template>
  <div class="supermarket-buyer-dashboard">
    <!-- Dashboard Tab -->
    <BuyerDashboard v-if="activeTab === 'dashboard'" />
    
    <!-- Orders Tab -->
    <BuyerOrders v-if="activeTab === 'orders'" />
    
    <!-- Subscriptions Tab -->
    <BuyerSubscriptions v-if="activeTab === 'subscriptions'" />
    
    <!-- Profile Tab -->
    <BuyerProfile v-if="activeTab === 'profile'" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import BuyerDashboard from '@/components/business/supermarket/BuyerDashboard.vue'
import BuyerOrders from '@/components/business/supermarket/BuyerOrders.vue'
import BuyerSubscriptions from '@/components/business/supermarket/BuyerSubscriptions.vue'
import BuyerProfile from '@/components/business/supermarket/BuyerProfile.vue'

const activeTab = ref('dashboard')

// Listen for tab changes from child components
const handleTabChange = (tab: string) => {
  activeTab.value = tab
}

// Expose tab change method to child components via event bus or provide/inject if needed
defineExpose({
  setTab: (tab: string) => {
    activeTab.value = tab
  }
})
</script>
