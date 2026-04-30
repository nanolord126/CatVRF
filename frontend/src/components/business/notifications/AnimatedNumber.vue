<template>
  <span>{{ animatedValue }}</span>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'

const props = defineProps<{
  value: number
  duration?: number
  decimals?: number
}>()

const animatedValue = ref<string>('0')

const animateValue = (start: number, end: number, duration: number) => {
  const startTimestamp = performance.now()
  const step = (timestamp: number) => {
    const progress = Math.min((timestamp - startTimestamp) / duration, 1)
    const easeOutQuart = 1 - Math.pow(1 - progress, 4)
    const current = start + (end - start) * easeOutQuart
    animatedValue.value = current.toFixed(props.decimals || 0)
    if (progress < 1) {
      requestAnimationFrame(step)
    }
  }
  requestAnimationFrame(step)
}

onMounted(() => {
  animateValue(0, props.value, props.duration || 1000)
})

watch(() => props.value, (newValue) => {
  const currentValue = parseFloat(animatedValue.value)
  animateValue(currentValue, newValue, props.duration || 1000)
})
</script>
