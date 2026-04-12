<template>
  <div class="universal-card-wrap">
    <div ref="target" :style="cardStyle"
         class="universal-card relative isolate rounded-xl overflow-hidden cursor-pointer group shadow-lg transition-all duration-300"
         :class="compact ? 'aspect-[3/2] min-h-[130px]' : 'aspect-[3/2] min-h-[160px]'"
         @click="$emit('click', item)"
         @mousemove="onMouseMove" @mouseleave="onMouseLeave">

      <!-- Parallax Image Background -->
      <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 will-change-transform z-0"
           :style="bgStyle" :class="{ 'scale-110 group-hover:scale-125': !isMobile }">
        <img :src="item.image" :alt="item.name"
             class="w-full h-full object-cover transition-all duration-500"
             :class="item.inStock === false ? 'grayscale brightness-50' : 'grayscale-[0.15] group-hover:grayscale-0 contrast-110'" />
      </div>

      <!-- Gradient overlay — only price + CTA -->
      <div class="absolute inset-0 bg-linear-to-t from-black/70 via-transparent to-transparent flex flex-col justify-end p-2.5 z-10">

        <!-- B2B badge (top-left) -->
        <span v-if="item.isB2B || item.type === 'b2b'" class="absolute top-2 left-2 text-[9px] text-amber-300 font-black flex items-center gap-0.5 backdrop-blur-sm bg-amber-500/20 px-1.5 py-0.5 rounded-full border border-amber-500/30">
          🏢 B2B
        </span>

        <!-- Distance badge (top-right) -->
        <span v-if="geo" class="absolute top-2 right-2 text-[9px] text-white/60 font-semibold flex items-center gap-0.5 backdrop-blur-sm bg-black/20 px-1.5 py-0.5 rounded-full">
          {{ geo.icon }} {{ geo.text }}
        </span>

        <!-- Price + CTA row at bottom -->
        <div class="flex items-center justify-between">
          <div class="flex items-baseline gap-0.5">
            <span v-if="meta.pricePrefix" class="text-[9px] text-white/35 uppercase">{{ meta.pricePrefix }}</span>
            <span class="text-sm font-black text-white/90">{{ formatPrice(item.price) }}&nbsp;₽</span>
            <span v-if="item.pricePerNight || item.totalRooms" class="text-[9px] text-white/40">/ночь</span>
          </div>

          <button v-if="item.inStock !== false"
              @click.stop.prevent="$emit('action', item)"
              class="relative z-20 backdrop-blur-md px-2.5 py-1 rounded-lg text-white text-[10px] font-bold transition-all transform active:scale-90 hover:scale-110 hover:shadow-lg flex items-center gap-1 group/btn whitespace-nowrap"
              style="background: var(--t-primary); box-shadow: 0 2px 8px var(--t-glow);">
            {{ meta.cta }}
            <svg class="w-2.5 h-2.5 group-hover/btn:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
          </button>
          <span v-else class="text-[10px] text-white/35 font-bold italic">Нет в наличии</span>
        </div>
      </div>
    </div>

    <!-- ═══ Info BELOW the card ═══ -->
    <div class="mt-1.5 px-0.5 cursor-pointer" @click="$emit('click', item)">
      <h3 class="text-xs font-bold leading-tight line-clamp-2" style="color: var(--t-text);">
        {{ item.name }}
      </h3>

      <!-- Star rating (all item types) -->
      <div v-if="item.rating" class="flex items-center gap-1 mt-0.5">
        <span class="flex">
          <span v-for="s in 5" :key="s" class="text-[10px]" :style="{ color: s <= Math.round(item.rating) ? '#facc15' : 'rgba(128,128,128,.25)' }">★</span>
        </span>
        <span class="text-[10px] font-semibold" style="color: var(--t-text-3);">{{ item.rating }}</span>
      </div>

      <!-- Category / type label -->
      <p class="text-[10px] mt-0.5 truncate" style="color: var(--t-text-3);">
        {{ item.accommodationLabel || item.category || meta.badge }}
      </p>

      <!-- Subtitle (transport route, etc) -->
      <p v-if="item.subtitle" class="text-[10px] truncate" style="color: var(--t-text-3);">{{ item.subtitle }}</p>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from 'vue';
import { useMouseInElement } from '@vueuse/core';
import { typeMeta } from '@/data/verticals.js';
import { useUserGeo } from '@/composables/useUserGeo';

const props = defineProps({
    item:    { type: Object, required: true },
    type:    { type: String, default: 'product' },
    compact: { type: Boolean, default: false },
});

defineEmits(['click', 'action']);

const meta = computed(() => typeMeta[props.type] || typeMeta.product);

const { distanceToUser, geoLabel } = useUserGeo();

/* ── Гео-лейбл для карточки ── */
const geo = computed(() => {
    const dist = distanceToUser(props.item.lat, props.item.lng);
    return geoLabel(props.item.deliveryMode, dist, props.type);
});

const formatPrice = (p) => {
    const n = typeof p === 'number' ? p : parseInt(String(p).replace(/\s/g, ''), 10);
    return isNaN(n) ? p : n.toLocaleString('ru-RU');
};

/* ── 3D parallax ── */
const target = ref(null);
const isMobile = ref(false);
onMounted(() => { isMobile.value = window.innerWidth < 768; });

const { elementX, elementY, elementWidth, elementHeight, isOutside } = useMouseInElement(target);
const rot = reactive({ x: 0, y: 0 });

const onMouseMove = () => {
    if (isMobile.value || isOutside.value) return;
    rot.x = ((elementY.value / elementHeight.value) - 0.5) * -12;
    rot.y = ((elementX.value / elementWidth.value) - 0.5) * 12;
};
const onMouseLeave = () => { rot.x = 0; rot.y = 0; };

const cardStyle = computed(() => ({
    transform: `perspective(900px) rotateX(${rot.x}deg) rotateY(${rot.y}deg)`,
}));
const bgStyle = computed(() => ({
    transform: `translate3d(${rot.y * -0.4}px, ${rot.x * 0.4}px, 0)`,
}));
</script>

<style scoped>
.universal-card-wrap { display: flex; flex-direction: column; }
.universal-card { transition: transform 0.35s ease, box-shadow 0.35s ease; }
.universal-card:hover { box-shadow: 0 20px 50px rgba(0,0,0,.25); }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
