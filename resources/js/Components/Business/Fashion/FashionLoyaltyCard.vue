<template>
  <div class="fashion-loyalty-card bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl p-6 text-white shadow-lg">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-lg font-bold">Loyalty Program</h3>
        <p class="text-purple-200 text-sm">Earn points with every purchase</p>
      </div>
      <div class="text-right">
        <div class="text-3xl font-bold">{{ points }}</div>
        <div class="text-purple-200 text-sm">points</div>
      </div>
    </div>

    <div class="mb-4">
      <div class="flex justify-between text-sm mb-1">
        <span>{{ tier }}</span>
        <span>{{ nextTierPoints }} points to {{ nextTier }}</span>
      </div>
      <div class="w-full bg-purple-800 rounded-full h-2">
        <div
          class="bg-white rounded-full h-2 transition-all duration-300"
          :style="{ width: progress + '%' }"
        ></div>
      </div>
    </div>

    <div v-if="nftUnlocked" class="bg-white/20 rounded-lg p-3 mb-3">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center">
          <span class="text-purple-600 text-xl">🎨</span>
        </div>
        <div>
          <div class="font-semibold">NFT Avatar Unlocked!</div>
          <div class="text-sm text-purple-200">Exclusive digital collectible</div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-2 text-center text-sm">
      <div class="bg-white/10 rounded-lg p-2">
        <div class="font-semibold">{{ pointsPerPurchase }}</div>
        <div class="text-purple-200 text-xs">per purchase</div>
      </div>
      <div class="bg-white/10 rounded-lg p-2">
        <div class="font-semibold">{{ pointsPerTryOn }}</div>
        <div class="text-purple-200 text-xs">per try-on</div>
      </div>
      <div class="bg-white/10 rounded-lg p-2">
        <div class="font-semibold">{{ pointsPerReview }}</div>
        <div class="text-purple-200 text-xs">per review</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface LoyaltyData {
  currentPoints: number;
  tier: string;
  nextTier: string;
  nextTierPoints: number;
  nftUnlocked: boolean;
}

const props = defineProps<{
  userId: number;
}>();

const points = ref(0);
const tier = ref('Bronze');
const nextTier = ref('Silver');
const nextTierPoints = ref(1000);
const nftUnlocked = ref(false);

const progress = computed(() => {
  const tierThresholds: Record<string, number> = {
    Bronze: 0,
    Silver: 1000,
    Gold: 5000,
    Platinum: 10000,
  };

  const currentTierThreshold = tierThresholds[tier.value] || 0;
  const nextTierThreshold = tierThresholds[nextTier.value] || 10000;
  const range = nextTierThreshold - currentTierThreshold;
  const current = points.value - currentTierThreshold;

  return range > 0 ? Math.min((current / range) * 100, 100) : 0;
});

const pointsPerPurchase = computed(() => 100);
const pointsPerTryOn = computed(() => 10);
const pointsPerReview = computed(() => 50);

onMounted(async () => {
  try {
    const response = await fetch(`/api/fashion/loyalty/${props.userId}`);
    const data: LoyaltyData = await response.json();
    points.value = data.currentPoints;
    tier.value = data.tier;
    nextTier.value = data.nextTier;
    nextTierPoints.value = data.nextTierPoints;
    nftUnlocked.value = data.nftUnlocked;
  } catch (error) {
    console.error('Failed to load loyalty data:', error);
  }
});
</script>
