<template>
  <div class="fashion-sustainability-badge bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-4 text-white">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
      </div>
      <div>
        <div class="font-bold text-lg">Sustainable Fashion</div>
        <div class="text-sm text-green-100">{{ sustainabilityMessage }}</div>
      </div>
      <div class="ml-auto text-right">
        <div class="text-3xl font-bold">{{ score }}%</div>
        <div class="text-xs text-green-100">Eco Score</div>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
      <div class="bg-white/10 rounded-lg p-2">
        <div class="font-semibold">{{ environmentalScore }}%</div>
        <div class="text-xs text-green-100">Environmental</div>
      </div>
      <div class="bg-white/10 rounded-lg p-2">
        <div class="font-semibold">{{ socialScore }}%</div>
        <div class="text-xs text-green-100">Social</div>
      </div>
      <div class="bg-white/10 rounded-lg p-2">
        <div class="font-semibold">{{ governanceScore }}%</div>
        <div class="text-xs text-green-100">Governance</div>
      </div>
    </div>

    <div v-if="certifications.length > 0" class="mt-4">
      <div class="text-xs text-green-100 mb-2">Certifications:</div>
      <div class="flex flex-wrap gap-1">
        <span
          v-for="cert in certifications"
          :key="cert"
          class="bg-white/20 px-2 py-1 rounded text-xs"
        >
          {{ cert }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  score: number;
  environmentalScore?: number;
  socialScore?: number;
  governanceScore?: number;
  certifications?: string[];
}>();

const environmentalScore = computed(() => props.environmentalScore ?? props.score);
const socialScore = computed(() => props.socialScore ?? props.score);
const governanceScore = computed(() => props.governanceScore ?? props.score);
const certifications = computed(() => props.certifications ?? []);

const sustainabilityMessage = computed(() => {
  if (props.score >= 80) return 'Excellent sustainability practices';
  if (props.score >= 60) return 'Good sustainability practices';
  if (props.score >= 40) return 'Moderate sustainability practices';
  return 'Improving sustainability';
});
</script>
