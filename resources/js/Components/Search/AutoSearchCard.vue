<template>
  <div class="auto-search-card">
    <SearchCard
      :id="id"
      vertical="auto"
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
  // Auto-specific criteria (vertical_restricted - только для Auto)
  brand?: string;
  year?: number;
  mileage?: number;
  transmission?: 'automatic' | 'manual';
  fuelType?: 'petrol' | 'diesel' | 'electric' | 'hybrid';
  bodyType?: string;
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: '',
  badge: '',
  isFeatured: false,
  actionLabel: 'Подробнее',
  brand: '',
  year: 0,
  mileage: 0,
  transmission: 'automatic',
  fuelType: 'petrol',
  bodyType: '',
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const transmissionNames: Record<string, string> = {
  automatic: 'Автомат',
  manual: 'Механика',
};

const fuelTypeNames: Record<string, string> = {
  petrol: 'Бензин',
  diesel: 'Дизель',
  electric: 'Электро',
  hybrid: 'Гибрид',
};

const allCriteria = computed(() => {
  const criteria = [];

  // Публичные критерии (индексируются для всех вертикалей)
  // (унаследованы из SearchCard)

  // Вертикально-специфичные критерии (только для Auto)
  // Эти критерии НЕ появятся в Beauty, Medical, Hotels и т.д.
  if (props.brand) {
    criteria.push({
      code: 'brand',
      label: 'Марка',
      value: props.brand,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.year > 0) {
    criteria.push({
      code: 'year',
      label: 'Год',
      value: props.year,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.mileage > 0) {
    criteria.push({
      code: 'mileage',
      label: 'Пробег',
      value: `${props.mileage.toLocaleString()} км`,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.transmission) {
    criteria.push({
      code: 'transmission',
      label: 'КПП',
      value: transmissionNames[props.transmission],
      type: 'vertical_restricted' as const,
    });
  }

  if (props.fuelType) {
    criteria.push({
      code: 'fuel_type',
      label: 'Топливо',
      value: fuelTypeNames[props.fuelType],
      type: 'vertical_restricted' as const,
    });
  }

  if (props.bodyType) {
    criteria.push({
      code: 'body_type',
      label: 'Кузов',
      value: props.bodyType,
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
.auto-search-card {
  width: 100%;
}
</style>
