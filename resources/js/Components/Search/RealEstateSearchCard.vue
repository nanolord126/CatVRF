<template>
  <div class="real-estate-search-card">
    <SearchCard
      :id="id"
      vertical="real_estate"
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
  propertyType?: string;
  areaSqM?: number;
  rooms?: number;
  floor?: number;
  hasBalcony?: boolean;
  hasParking?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: 'месяц',
  badge: '',
  isFeatured: false,
  actionLabel: 'Подробнее',
  propertyType: '',
  areaSqM: 0,
  rooms: 0,
  floor: 0,
  hasBalcony: false,
  hasParking: false,
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const propertyTypeNames: Record<string, string> = {
  apartment: 'Квартира',
  house: 'Дом',
  studio: 'Студия',
  penthouse: 'Пентхаус',
  commercial: 'Коммерческое',
};

const allCriteria = computed(() => {
  const criteria = [];

  if (props.propertyType) {
    criteria.push({
      code: 'property_type',
      label: 'Тип',
      value: propertyTypeNames[props.propertyType] || props.propertyType,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.areaSqM > 0) {
    criteria.push({
      code: 'area_sqm',
      label: 'Площадь',
      value: `${props.areaSqM} м²`,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.rooms > 0) {
    criteria.push({
      code: 'rooms',
      label: 'Комнат',
      value: props.rooms,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.floor > 0) {
    criteria.push({
      code: 'floor',
      label: 'Этаж',
      value: props.floor,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasBalcony) {
    criteria.push({
      code: 'has_balcony',
      label: 'Балкон',
      value: 'Есть',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasParking) {
    criteria.push({
      code: 'has_parking',
      label: 'Парковка',
      value: 'Есть',
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
.real-estate-search-card {
  width: 100%;
}
</style>
