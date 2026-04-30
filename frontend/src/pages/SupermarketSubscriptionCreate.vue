<template>
  <SubscriptionCreate @back="handleBack" @created="handleCreated" />
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import SubscriptionCreate from '@/components/business/supermarket/SubscriptionCreate.vue'

const router = useRouter()

const handleBack = () => {
  router.back()
}

const handleCreated = () => {
  router.push('/supermarket/subscriptions')
}
</script>
