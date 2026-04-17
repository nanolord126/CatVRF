<template>
  <div class="hotels-search-card">
    <SearchCard
      :id="id"
      vertical="hotels"
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
  // Hotels-specific criteria (vertical_restricted - только для Hotels)
  stars?: number;
  hasPool?: boolean;
  hasSpa?: boolean;
  hasGym?: boolean;
  hasBreakfast?: boolean;
  hasSeaView?: boolean;
  distanceToSea?: number;
  distanceToBeach?: number;
  propertyType?: string;
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: 'ночь',
  badge: '',
  isFeatured: false,
  actionLabel: 'Забронировать',
  stars: 0,
  hasPool: false,
  hasSpa: false,
  hasGym: false,
  hasBreakfast: false,
  hasSeaView: false,
  distanceToSea: 0,
  distanceToBeach: 0,
  propertyType: '',
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const propertyTypeNames: Record<string, string> = {
  hotel: 'Отель',
  sanatorium: 'Санаторий',
  boarding_house: 'Пансионат',
  recreation_center: 'Дом отдыха',
  apartment_daily: 'Квартира посуточно',
  aparthotel: 'Апарт-отель',
  hostel: 'Хостел',
  guest_house: 'Гостевой дом',
  villa: 'Вилла',
};

const allCriteria = computed(() => {
  const criteria = [];

  // Публичные критерии (индексируются для всех вертикалей)
  // (унаследованы из SearchCard)

  // Вертикально-специфичные критерии (только для Hotels)
  // Эти критерии НЕ появятся в Beauty, Auto, Medical и т.д.
  if (props.stars > 0) {
    criteria.push({
      code: 'stars',
      label: 'Звёзды',
      value: `${props.stars} ⭐`,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasPool) {
    criteria.push({
      code: 'has_pool',
      label: 'Бассейн',
      value: 'Есть',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasSpa) {
    criteria.push({
      code: 'has_spa',
      label: 'SPA',
      value: 'Есть',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasGym) {
    criteria.push({
      code: 'has_gym',
      label: 'Фитнес',
      value: 'Есть',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasBreakfast) {
    criteria.push({
      code: 'has_breakfast',
      label: 'Завтрак',
      value: 'Включён',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasSeaView) {
    criteria.push({
      code: 'has_sea_view',
      label: 'Вид на море',
      value: 'Есть',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.distanceToSea > 0) {
    criteria.push({
      code: 'distance_to_sea',
      label: 'До моря',
      value: `${props.distanceToSea} м`,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.distanceToBeach > 0) {
    criteria.push({
      code: 'distance_to_beach',
      label: 'До пляжа',
      value: `${props.distanceToBeach} м`,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.propertyType) {
    criteria.push({
      code: 'property_type',
      label: 'Тип',
      value: propertyTypeNames[props.propertyType] || props.propertyType,
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
.hotels-search-card {
  width: 100%;
}
</style>
