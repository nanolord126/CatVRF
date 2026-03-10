<template>
  <div ref="target" :style="cardStyle" 
       class="relative rounded-3xl overflow-hidden cursor-pointer group shadow-2xl transition-all duration-300"
       @mousemove="onMouseMove" @mouseleave="onMouseLeave">
    <!-- Image Parallax Background -->
    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 will-change-transform"
         :style="backgroundStyle" :class="{ 'scale-110 group-hover:scale-125': !isMobile }">
      <img :src="product.image" class="w-full h-full object-cover grayscale-[0.2] group-hover:grayscale-0 contrast-125" />
    </div>

    <!-- Glassmorphism Content Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-6 group-hover:from-black">
      <div class="space-y-2 translate-z-10 transition-transform duration-300 group-hover:translate-z-20">
        <span class="text-xs uppercase font-extrabold text-blue-400 opacity-60">{{ product.category }}</span>
        <h3 class="text-xl font-black text-white leading-tight line-clamp-2 drop-shadow-lg">{{ product.name }}</h3>
        
        <div class="flex items-center justify-between mt-4">
          <span class="text-2xl font-black text-white/90 underline decoration-blue-500/50 decoration-4 shadow-white/10">{{ product.price }} ₽</span>
          <button class="bg-white/10 hover:bg-white/20 backdrop-blur-md px-4 py-2 rounded-xl text-white text-xs font-bold border border-white/20 hover:border-white transition-all transform active:scale-95 flex items-center gap-2 group/btn">
            Buy
            <svg class="w-3 h-3 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref } from 'vue';
import { useMouseInElement } from '@vueuse/core';

const props = defineProps({ product: Object });
const target = ref(null);
const { elementX, elementY, elementWidth, elementHeight, isOutside } = useMouseInElement(target);

const onMouseMove = (e) => {
  if (isOutside.value) return;
  const rotationX = ((elementY.value / elementHeight.value) - 0.5) * -15;
  const rotationY = ((elementX.value / elementWidth.value) - 0.5) * 15;
  cardRotation.x = rotationX;
  cardRotation.y = rotationY;
};

const onMouseLeave = () => { cardRotation.x = 0; cardRotation.y = 0; };
const cardRotation = reactive({ x: 0, y: 0 });

const cardStyle = computed(() => ({
  transform: `perspective(1000px) rotateX(${cardRotation.x}deg) rotateY(${cardRotation.y}deg) scale3d(1,1,1)`,
}));

const backgroundStyle = computed(() => ({
  transform: `translate3d(${cardRotation.y * -0.5}px, ${cardRotation.x * 0.5}px, 0)`,
}));
</script>

<style scoped>
.translate-z-10 { transform: translateZ(10px); }
.translate-z-20 { transform: translateZ(20px); }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
