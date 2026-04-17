<template>
  <div class="travel-search-card">
    <SearchCard
      :id="id"
      vertical="travel"
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
  tripType?: string;
  durationDays?: number;
  destination?: string;
  isAllInclusive?: boolean;
  hasGuide?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: '',
  badge: '',
  isFeatured: false,
  actionLabel: 'Забронировать',
  tripType: '',
  durationDays: 0,
  destination: '',
  isAllInclusive: false,
  hasGuide: false,
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const tripTypeNames: Record<string, string> = {
  beach: 'Пляжный',
  adventure: 'Приключения',
  cultural: 'Культурный',
  safari: 'Сафари',
  cruise: 'Круиз',
  ski: 'Лыжный',
};

const allCriteria = computed(() => {
  const criteria = [];

  if (props.tripType) {
    criteria.push({
      code: 'trip_type',
      label: 'Тип',
      value: tripTypeNames[props.tripType] || props.tripType,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.durationDays > 0) {
    criteria.push({
      code: 'duration_days',
      label: 'Длительность',
      value: `${props.durationDays} дн`,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.destination) {
    criteria.push({
      code: 'destination',
      label: 'Направление',
      value: props.destination,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.isAllInclusive) {
    criteria.push({
      code: 'all_inclusive',
      label: 'Все включено',
      value: 'Да',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasGuide) {
    criteria.push({
      code: 'has_guide',
      label: 'Гид',
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
.travel-search-card {
  width: 100%;
}
</style>
