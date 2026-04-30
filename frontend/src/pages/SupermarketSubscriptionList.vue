<template>
  <SubscriptionList @back="handleBack" @create="handleCreate" @pause="handlePause" @resume="handleResume" @edit="handleEdit" @track="handleTrack" @cancel="handleCancel" />
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import SubscriptionList from '@/components/business/supermarket/SubscriptionList.vue'

const router = useRouter()

const handleBack = () => {
  router.back()
}

const handleCreate = () => {
  router.push('/supermarket/subscriptions/create')
}

const handlePause = (id: number) => {
  console.log('Pause subscription:', id)
  // API call already handled in component
}

const handleResume = (id: number) => {
  console.log('Resume subscription:', id)
  // API call already handled in component
}

const handleEdit = (id: number) => {
  router.push(`/supermarket/subscriptions/${id}/edit`)
}

const handleTrack = (id: number) => {
  router.push(`/supermarket/subscriptions/${id}/track`)
}

const handleCancel = (id: number) => {
  console.log('Cancel subscription:', id)
  // API call already handled in component
}
</script>
