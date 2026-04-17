<template>
  <div class="beauty-search-card">
    <SearchCard
      :id="id"
      vertical="beauty"
      :title="title"
      :description="description"
      :image-url="imageUrl"
      :rating="rating"
      :price="price"
      :price-period="pricePeriod"
      :badge="badge"
      :is-featured="isFeatured"
      :action-label="actionLabel"
      :criteria="allCriteria"
      @action="handleAction"
      @favorite="handleFavorite"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SearchCard from './SearchCard.vue';

interface Props {
  id: string | number;
  title: string;
  description: string;
  imageUrl?: string;
  rating?: number;
  price: number;
  pricePeriod?: string;
  badge?: string;
  isFeatured?: boolean;
  actionLabel?: string;
  // Beauty-specific criteria
  serviceType?: string;
  duration?: number;
  isMobileService?: boolean;
  hasDiscount?: boolean;
  discountPercent?: number;
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: '',
  badge: '',
  isFeatured: false,
  actionLabel: 'Записаться',
  serviceType: '',
  duration: 0,
  isMobileService: false,
  hasDiscount: false,
  discountPercent: 0,
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const serviceTypeNames: Record<string, string> = {
  manicure: 'Маникюр',
  pedicure: 'Педикюр',
  haircut: 'Стрижка',
  coloring: 'Окрашивание',
  styling: 'Укладка',
  facial: 'Уход за лицом',
  massage: 'Массаж',
  makeup: 'Макияж',
  eyelash: 'Ресницы',
  eyebrow: 'Брови',
};

const allCriteria = computed(() => {
  const criteria = [];

  // Публичные критерии (индексируются)
  if (props.hasDiscount) {
    criteria.push({
      code: 'has_discount',
      label: 'Скидка',
      value: `-${props.discountPercent}%`,
      type: 'public' as const,
    });
  }

  // Вертикально-специфичные критерии (Beauty)
  if (props.serviceType) {
    criteria.push({
      code: 'service_type',
      label: 'Услуга',
      value: serviceTypeNames[props.serviceType] || props.serviceType,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.duration > 0) {
    criteria.push({
      code: 'duration',
      label: 'Длительность',
      value: `${props.duration} мин`,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.isMobileService) {
    criteria.push({
      code: 'mobile_service',
      label: 'Выезд на дом',
      value: 'Да',
      type: 'vertical_restricted' as const,
    });
  }

  return criteria;
});

const handleAction = (id: string | number) => {
  emit('action', id);
};

const handleFavorite = (id: string | number) => {
  emit('favorite', id);
};
</script>

<style scoped>
.beauty-search-card {
  width: 100%;
}
</style>
