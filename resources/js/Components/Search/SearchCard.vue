<template>
  <div class="search-card" :class="[`search-card--${vertical}`, { 'search-card--featured': isFeatured }]">
    <div class="search-card__image" v-if="imageUrl">
      <img :src="imageUrl" :alt="title" class="search-card__img" />
      <div class="search-card__badge" v-if="badge">{{ badge }}</div>
      <div class="search-card__favorite" @click="toggleFavorite">
        <svg v-if="!isFavorite" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="currentColor" class="favorite-active">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
      </div>
    </div>

    <div class="search-card__content">
      <div class="search-card__header">
        <span class="search-card__vertical">{{ verticalName }}</span>
        <div class="search-card__rating" v-if="rating">
          <svg viewBox="0 0 24 24" fill="currentColor" class="star-icon">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
          </svg>
          <span>{{ rating }}</span>
        </div>
      </div>

      <h3 class="search-card__title">{{ title }}</h3>
      <p class="search-card__description">{{ description }}</p>

      <!-- Публичные критерии (индексируются) -->
      <div class="search-card__criteria" v-if="publicCriteria.length > 0">
        <div class="search-card__criteria-item" v-for="criterion in publicCriteria" :key="criterion.code">
          <span class="criteria-label">{{ criterion.label }}</span>
          <span class="criteria-value">{{ criterion.value }}</span>
        </div>
      </div>

      <!-- Критерии, ограниченные вертикалью -->
      <div class="search-card__vertical-criteria" v-if="verticalCriteria.length > 0">
        <div class="search-card__criteria-item" v-for="criterion in verticalCriteria" :key="criterion.code">
          <span class="criteria-label">{{ criterion.label }}</span>
          <span class="criteria-value">{{ criterion.value }}</span>
        </div>
      </div>

      <div class="search-card__footer">
        <div class="search-card__price">
          <span class="price-label">от</span>
          <span class="price-value">{{ formatPrice(price) }}</span>
          <span class="price-period" v-if="pricePeriod">/{{ pricePeriod }}</span>
        </div>
        <button @click="handleAction" class="search-card__action">
          {{ actionLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface SearchCriterion {
  code: string;
  label: string;
  value: string | number | boolean;
  type: 'public' | 'vertical_restricted';
}

interface Props {
  id: string | number;
  vertical: string;
  title: string;
  description: string;
  imageUrl?: string;
  rating?: number;
  price: number;
  pricePeriod?: string;
  badge?: string;
  isFeatured?: boolean;
  actionLabel?: string;
  criteria?: SearchCriterion[];
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: '',
  badge: '',
  isFeatured: false,
  actionLabel: 'Подробнее',
  criteria: () => [],
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const isFavorite = ref(false);

const verticalName = computed(() => {
  const names: Record<string, string> = {
    hotels: 'Отели',
    beauty: 'Красота',
    auto: 'Авто',
    medical: 'Медицина',
    food: 'Еда',
    travel: 'Путешествия',
    fitness: 'Фитнес',
    real_estate: 'Недвижимость',
  };
  return names[props.vertical] || props.vertical;
});

const publicCriteria = computed(() => {
  return props.criteria.filter(c => c.type === 'public');
});

const verticalCriteria = computed(() => {
  return props.criteria.filter(c => c.type === 'vertical_restricted');
});

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price);
};

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value;
  emit('favorite', props.id);
};

const handleAction = () => {
  emit('action', props.id);
};
</script>

<style scoped>
.search-card {
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  cursor: pointer;
}

.search-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  transform: translateY(-4px);
}

.search-card--featured {
  box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
  border: 2px solid #3b82f6;
}

.search-card__image {
  position: relative;
  width: 100%;
  height: 200px;
  overflow: hidden;
}

.search-card__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.search-card__badge {
  position: absolute;
  top: 12px;
  left: 12px;
  background: #3b82f6;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.search-card__favorite {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.search-card__favorite:hover {
  background: white;
  transform: scale(1.1);
}

.search-card__favorite svg {
  width: 20px;
  height: 20px;
  color: #ef4444;
}

.search-card__favorite .favorite-active {
  fill: #ef4444;
}

.search-card__content {
  padding: 20px;
}

.search-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.search-card__vertical {
  font-size: 12px;
  font-weight: 600;
  color: #3b82f6;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.search-card__rating {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  font-weight: 600;
  color: #f59e0b;
}

.star-icon {
  width: 16px;
  height: 16px;
}

.search-card__title {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.4;
}

.search-card__description {
  margin: 0 0 16px 0;
  font-size: 14px;
  color: #6b7280;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.search-card__criteria {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.search-card__vertical-criteria {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}

.search-card__criteria-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  background: #f3f4f6;
  border-radius: 6px;
  font-size: 12px;
}

.search-card__vertical-criteria .search-card__criteria-item {
  background: white;
  border: 1px solid #e5e7eb;
}

.criteria-label {
  color: #6b7280;
}

.criteria-value {
  font-weight: 600;
  color: #1f2937;
}

.search-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.search-card__price {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.price-label {
  font-size: 13px;
  color: #6b7280;
}

.price-value {
  font-size: 24px;
  font-weight: 700;
  color: #3b82f6;
}

.price-period {
  font-size: 13px;
  color: #6b7280;
}

.search-card__action {
  padding: 10px 20px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.search-card__action:hover {
  background: #2563eb;
}

/* Адаптивность */
@media (max-width: 768px) {
  .search-card__image {
    height: 160px;
  }

  .search-card__content {
    padding: 16px;
  }

  .search-card__title {
    font-size: 16px;
  }

  .search-card__description {
    font-size: 13px;
  }

  .search-card__price {
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
  }

  .search-card__footer {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .search-card__action {
    width: 100%;
  }
}
</style>
