<template>
  <SubscriptionDetail :subscription-id="subscriptionId" @back="handleBack" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import SubscriptionDetail from '@/components/business/supermarket/SubscriptionDetail.vue'

const router = useRouter()
const route = useRoute()

const subscriptionId = computed(() => route.params.id as string)

const handleBack = () => {
  router.back()
}
</script>
