# Blade → Vue Migration Guide
## CatVRF 2026 - Section 8 Implementation

**Date:** 25.04.2026  
**Estimated Time:** 30-40 hours  
**Status:** 🔄 IN PROGRESS

---

## Overview

This guide provides detailed steps for migrating 79 legacy Blade components to Vue.js components, following modern frontend best practices and the CatVRF 2026 Canon.

---

## Migration Strategy

### Phase 1: Analysis and Planning (4-6 hours)

#### 1.1 Inventory Blade Components

**Script:** `scripts/analyze-blade-components.php`

```php
<?php

declare(strict_types=1);

$bladeDir = resource_path('views/components');
$components = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($bladeDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $relativePath = str_replace($bladeDir . '/', '', $file->getPathname());
        $components[] = [
            'path' => $relativePath,
            'full_path' => $file->getPathname(),
            'size' => $file->getSize(),
            'dependencies' => analyzeBladeDependencies($file->getPathname()),
        ];
    }
}

// Sort by size (largest first for priority)
usort($components, fn($a, $b) => $b['size'] <=> $a['size']);

echo json_encode($components, JSON_PRETTY_PRINT);

function analyzeBladeDependencies(string $filePath): array
{
    $content = file_get_contents($filePath);
    $dependencies = [];
    
    // Check for @include, @component, @yield, @section
    preg_match_all('/@include\([\'"]([^\'"]+)[\'"]\)/', $content, $includes);
    $dependencies['includes'] = $includes[1] ?? [];
    
    preg_match_all('/@component\([\'"]([^\'"]+)[\'"]\)/', $content, $components);
    $dependencies['components'] = $components[1] ?? [];
    
    preg_match_all('/@yield\([\'"]([^\'"]+)[\'"]\)/', $content, $yields);
    $dependencies['yields'] = $yields[1] ?? [];
    
    return $dependencies;
}
```

#### 1.2 Prioritize Components for Migration

**Priority Criteria:**
1. **High Priority:** Critical user-facing components (forms, cards, navigation)
2. **Medium Priority:** Reusable UI components (buttons, inputs, modals)
3. **Low Priority:** One-off components, rarely used components

**Migration Order:**
1. Configurators (58 files) - Start with most used
2. UI components (12 files) - Foundation for other migrations
3. Other components (9 files) - Remaining

---

### Phase 2: Vue UI Kit Foundation (8-12 hours)

#### 2.1 Base Vue Component Structure

**File:** `frontend/src/components/ui/BaseComponent.vue`

```vue
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface BaseProps {
  id?: string;
  class?: string;
  testId?: string;
}

const props = defineProps<BaseProps>();

const componentId = computed(() => props.id || `component-${Math.random().toString(36).substr(2, 9)}`);
const testAttribute = computed(() => props.testId || componentId.value);
</script>

<template>
  <div 
    :id="componentId" 
    :class="props.class"
    :data-testid="testAttribute"
  >
    <slot />
  </div>
</template>
```

#### 2.2 Button Component

**File:** `frontend/src/components/ui/Button.vue`

```vue
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface ButtonProps {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
  disabled?: boolean;
  loading?: boolean;
  type?: 'button' | 'submit' | 'reset';
  fullWidth?: boolean;
  testId?: string;
}

const props = withDefaults(defineProps<ButtonProps>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
  fullWidth: false,
});

const emit = defineEmits<{
  click: [event: MouseEvent];
}>();

const buttonClasses = computed(() => [
  'inline-flex',
  'items-center',
  'justify-center',
  'font-medium',
  'rounded-lg',
  'transition-all',
  'duration-200',
  'focus:outline-none',
  'focus:ring-2',
  'focus:ring-offset-2',
  {
    // Variants
    'bg-blue-600': props.variant === 'primary',
    'text-white': props.variant === 'primary',
    'hover:bg-blue-700': props.variant === 'primary' && !props.disabled,
    'focus:ring-blue-500': props.variant === 'primary',
    
    'bg-gray-200': props.variant === 'secondary',
    'text-gray-900': props.variant === 'secondary',
    'hover:bg-gray-300': props.variant === 'secondary' && !props.disabled,
    'focus:ring-gray-500': props.variant === 'secondary',
    
    'bg-red-600': props.variant === 'danger',
    'text-white': props.variant === 'danger',
    'hover:bg-red-700': props.variant === 'danger' && !props.disabled,
    'focus:ring-red-500': props.variant === 'danger',
    
    'bg-transparent': props.variant === 'ghost',
    'text-gray-700': props.variant === 'ghost',
    'hover:bg-gray-100': props.variant === 'ghost' && !props.disabled,
    'focus:ring-gray-500': props.variant === 'ghost',
    
    // Sizes
    'px-3 py-1.5 text-sm': props.size === 'sm',
    'px-4 py-2 text-base': props.size === 'md',
    'px-6 py-3 text-lg': props.size === 'lg',
    
    // States
    'opacity-50 cursor-not-allowed': props.disabled || props.loading,
    'w-full': props.fullWidth,
  },
]);

const handleClick = (event: MouseEvent) => {
  if (!props.disabled && !props.loading) {
    emit('click', event);
  }
};
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    :data-testid="testId"
    @click="handleClick"
  >
    <span v-if="loading" class="mr-2">
      <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </span>
    <slot />
  </button>
</template>
```

#### 2.3 Input Component

**File:** `frontend/src/components/ui/Input.vue`

```vue
<script setup lang="ts">
import { computed, ref, type PropType } from 'vue';

interface InputProps {
  modelValue: string | number;
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url';
  placeholder?: string;
  disabled?: boolean;
  readonly?: boolean;
  error?: string;
  label?: string;
  required?: boolean;
  testId?: string;
  autofocus?: boolean;
}

const props = withDefaults(defineProps<InputProps>(), {
  type: 'text',
  disabled: false,
  readonly: false,
  required: false,
  autofocus: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | number];
  blur: [event: FocusEvent];
  focus: [event: FocusEvent];
  enter: [event: KeyboardEvent];
}>();

const inputRef = ref<HTMLInputElement | null>(null);

const inputClasses = computed(() => [
  'w-full',
  'px-3',
  'py-2',
  'border',
  'rounded-lg',
  'transition-all',
  'duration-200',
  'focus:outline-none',
  'focus:ring-2',
  {
    'border-red-500': props.error,
    'focus:ring-red-500': props.error,
    'border-gray-300': !props.error,
    'focus:ring-blue-500': !props.error,
    'focus:border-blue-500': !props.error,
    'bg-gray-100': props.disabled,
    'cursor-not-allowed': props.disabled,
    'bg-gray-50': props.readonly,
  },
]);

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  emit('update:modelValue', target.value);
};

const handleBlur = (event: FocusEvent) => {
  emit('blur', event);
};

const handleFocus = (event: FocusEvent) => {
  emit('focus', event);
};

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    emit('enter', event);
  }
};

if (props.autofocus) {
  setTimeout(() => inputRef.value?.focus(), 100);
}
</script>

<template>
  <div class="w-full">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <input
      ref="inputRef"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :class="inputClasses"
      :data-testid="testId"
      @input="handleInput"
      @blur="handleBlur"
      @focus="handleFocus"
      @keydown="handleKeydown"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
```

#### 2.4 Select Component

**File:** `frontend/src/components/ui/Select.vue`

```vue
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface SelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

interface SelectProps {
  modelValue: string | number;
  options: SelectOption[];
  placeholder?: string;
  disabled?: boolean;
  error?: string;
  label?: string;
  required?: boolean;
  testId?: string;
}

const props = withDefaults(defineProps<SelectProps>(), {
  disabled: false,
  required: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | number];
  change: [value: string | number];
}>();

const selectClasses = computed(() => [
  'w-full',
  'px-3',
  'py-2',
  'border',
  'rounded-lg',
  'transition-all',
  'duration-200',
  'focus:outline-none',
  'focus:ring-2',
  'bg-white',
  {
    'border-red-500': props.error,
    'focus:ring-red-500': props.error,
    'border-gray-300': !props.error,
    'focus:ring-blue-500': !props.error,
    'focus:border-blue-500': !props.error,
    'bg-gray-100': props.disabled,
    'cursor-not-allowed': props.disabled,
  },
]);

const handleChange = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  emit('update:modelValue', target.value);
  emit('change', target.value);
};
</script>

<template>
  <div class="w-full">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <select
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      :class="selectClasses"
      :data-testid="testId"
      @change="handleChange"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
```

#### 2.5 Modal Component

**File:** `frontend/src/components/ui/Modal.vue`

```vue
<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch, type PropType } from 'vue';

interface ModalProps {
  modelValue: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
  closable?: boolean;
  testId?: string;
}

const props = withDefaults(defineProps<ModalProps>(), {
  size: 'md',
  closable: true,
});

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  close: [];
  open: [];
}>();

const modalClasses = computed(() => [
  'fixed',
  'inset-0',
  'z-50',
  'flex',
  'items-center',
  'justify-center',
  {
    'p-4': props.size !== 'full',
  },
]);

const overlayClasses = computed(() => [
  'absolute',
  'inset-0',
  'bg-black',
  'bg-opacity-50',
  'transition-opacity',
  'duration-300',
]);

const contentClasses = computed(() => [
  'relative',
  'bg-white',
  'rounded-lg',
  'shadow-xl',
  'max-h-full',
  'overflow-y-auto',
  'transition-all',
  'duration-300',
  {
    'w-full max-w-sm': props.size === 'sm',
    'w-full max-w-md': props.size === 'md',
    'w-full max-w-lg': props.size === 'lg',
    'w-full max-w-2xl': props.size === 'xl',
    'w-full h-full': props.size === 'full',
  },
]);

const handleClose = () => {
  if (props.closable) {
    emit('update:modelValue', false);
    emit('close');
  }
};

const handleEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.modelValue && props.closable) {
    handleClose();
  }
};

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    emit('open');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

onMounted(() => {
  document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape);
  document.body.style.overflow = '';
});
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" :class="modalClasses" :data-testid="testId">
        <div :class="overlayClasses" @click="handleClose"></div>
        
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-300"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="modelValue" :class="contentClasses">
            <div v-if="title || closable" class="flex items-center justify-between p-4 border-b">
              <h3 v-if="title" class="text-lg font-semibold text-gray-900">{{ title }}</h3>
              <button
                v-if="closable"
                @click="handleClose"
                class="text-gray-400 hover:text-gray-600 focus:outline-none"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <div class="p-4">
              <slot />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
```

#### 2.6 Card Component

**File:** `frontend/src/components/ui/Card.vue`

```vue
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface CardProps {
  hoverable?: boolean;
  bordered?: boolean;
  shadow?: 'none' | 'sm' | 'md' | 'lg';
  padding?: 'none' | 'sm' | 'md' | 'lg';
  testId?: string;
}

const props = withDefaults(defineProps<CardProps>(), {
  hoverable: false,
  bordered: true,
  shadow: 'md',
  padding: 'md',
});

const cardClasses = computed(() => [
  'rounded-lg',
  'bg-white',
  'transition-all',
  'duration-200',
  {
    'border border-gray-200': props.bordered,
    'shadow-none': props.shadow === 'none',
    'shadow-sm': props.shadow === 'sm',
    'shadow-md': props.shadow === 'md',
    'shadow-lg': props.shadow === 'lg',
    'hover:shadow-lg hover:-translate-y-1': props.hoverable,
    'p-0': props.padding === 'none',
    'p-3': props.padding === 'sm',
    'p-4': props.padding === 'md',
    'p-6': props.padding === 'lg',
  },
]);
</script>

<template>
  <div :class="cardClasses" :data-testid="testId">
    <slot />
  </div>
</template>
```

#### 2.7 Badge Component

**File:** `frontend/src/components/ui/Badge.vue`

```vue
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface BadgeProps {
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info';
  size?: 'sm' | 'md' | 'lg';
  testId?: string;
}

const props = withDefaults(defineProps<BadgeProps>(), {
  variant: 'primary',
  size: 'md',
});

const badgeClasses = computed(() => [
  'inline-flex',
  'items-center',
  'font-medium',
  'rounded-full',
  {
    // Variants
    'bg-blue-100 text-blue-800': props.variant === 'primary',
    'bg-gray-100 text-gray-800': props.variant === 'secondary',
    'bg-green-100 text-green-800': props.variant === 'success',
    'bg-red-100 text-red-800': props.variant === 'danger',
    'bg-yellow-100 text-yellow-800': props.variant === 'warning',
    'bg-indigo-100 text-indigo-800': props.variant === 'info',
    
    // Sizes
    'px-2 py-0.5 text-xs': props.size === 'sm',
    'px-2.5 py-0.5 text-sm': props.size === 'md',
    'px-3 py-1 text-base': props.size === 'lg',
  },
]);
</script>

<template>
  <span :class="badgeClasses" :data-testid="testId">
    <slot />
  </span>
</template>
```

#### 2.8 Alert Component

**File:** `frontend/src/components/ui/Alert.vue`

```vue
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface AlertProps {
  variant?: 'info' | 'success' | 'warning' | 'danger';
  dismissible?: boolean;
  testId?: string;
}

const props = withDefaults(defineProps<AlertProps>(), {
  variant: 'info',
  dismissible: false,
});

const emit = defineEmits<{
  dismiss: [];
}>();

const alertClasses = computed(() => [
  'rounded-lg',
  'p-4',
  'border',
  {
    'bg-blue-50 border-blue-200 text-blue-800': props.variant === 'info',
    'bg-green-50 border-green-200 text-green-800': props.variant === 'success',
    'bg-yellow-50 border-yellow-200 text-yellow-800': props.variant === 'warning',
    'bg-red-50 border-red-200 text-red-800': props.variant === 'danger',
  },
]);

const iconClasses = computed(() => [
  'flex-shrink-0',
  {
    'text-blue-400': props.variant === 'info',
    'text-green-400': props.variant === 'success',
    'text-yellow-400': props.variant === 'warning',
    'text-red-400': props.variant === 'danger',
  },
]);

const icons = {
  info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
  success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
  warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
  danger: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
};
</script>

<template>
  <div :class="alertClasses" :data-testid="testId">
    <div class="flex">
      <div :class="iconClasses">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" v-html="icons[variant]" />
      </div>
      <div class="ml-3 flex-1">
        <slot />
      </div>
      <div v-if="dismissible" class="ml-auto pl-3">
        <button
          @click="emit('dismiss')"
          class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>
```

#### 2.9 Loading Spinner Component

**File:** `frontend/src/components/ui/Spinner.vue`

```vue
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface SpinnerProps {
  size?: 'sm' | 'md' | 'lg';
  color?: 'primary' | 'secondary' | 'white';
  testId?: string;
}

const props = withDefaults(defineProps<SpinnerProps>(), {
  size: 'md',
  color: 'primary',
});

const spinnerClasses = computed(() => [
  'animate-spin',
  'rounded-full',
  'border-2',
  {
    'h-4 w-4': props.size === 'sm',
    'h-8 w-8': props.size === 'md',
    'h-12 w-12': props.size === 'lg',
    'border-blue-600 border-t-transparent': props.color === 'primary',
    'border-gray-600 border-t-transparent': props.color === 'secondary',
    'border-white border-t-transparent': props.color === 'white',
  },
]);
</script>

<template>
  <div :class="spinnerClasses" :data-testid="testId" />
</template>
```

---

### Phase 3: Component Migration Templates (12-16 hours)

#### 3.1 Blade to Vue Migration Template

**Migration Pattern:**

```php
// BEFORE: Blade Component
// resources/views/components/product-card.blade.php
@props(['product', 'showPrice' => true])

<div class="product-card bg-white rounded-lg shadow-md p-4">
    <h2 class="text-xl font-bold">{{ $product->name }}</h2>
    @if($showPrice)
        <p class="text-gray-600">{{ number_format($product->price, 2) }} ₽</p>
    @endif
    <button class="btn-primary mt-4">Add to Cart</button>
</div>
```

```vue
// AFTER: Vue Component
// frontend/src/components/ui/ProductCard.vue
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface Product {
  id: number;
  name: string;
  price: number;
  image?: string;
}

interface ProductCardProps {
  product: Product;
  showPrice?: boolean;
}

const props = withDefaults(defineProps<ProductCardProps>(), {
  showPrice: true,
});

const emit = defineEmits<{
  addToCart: [product: Product];
}>();

const formattedPrice = computed(() => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(props.product.price);
});

const handleAddToCart = () => {
  emit('addToCart', props.product);
};
</script>

<template>
  <div class="product-card bg-white rounded-lg shadow-md p-4">
    <h2 class="text-xl font-bold">{{ product.name }}</h2>
    <p v-if="showPrice" class="text-gray-600">{{ formattedPrice }}</p>
    <Button class="mt-4" @click="handleAddToCart">
      Add to Cart
    </Button>
  </div>
</template>
```

#### 3.2 Configurator Component Migration Example

**File:** `frontend/src/components/configurators/ServiceConfigurator.vue`

```vue
<script setup lang="ts">
import { ref, computed, watch, type PropType } from 'vue';
import Input from '../ui/Input.vue';
import Select from '../ui/Select.vue';
import Button from '../ui/Button.vue';

interface ServiceConfiguratorProps {
  serviceType: string;
  initialConfig?: Record<string, any>;
}

const props = withDefaults(defineProps<ServiceConfiguratorProps>(), {
  initialConfig: () => ({}),
});

const emit = defineEmits<{
  configChange: [config: Record<string, any>];
  validate: [isValid: boolean];
}>();

const config = ref<Record<string, any>>({ ...props.initialConfig });
const errors = ref<Record<string, string>>({});

const serviceOptions = computed(() => {
  switch (props.serviceType) {
    case 'restaurant':
      return [
        { value: 'table_booking', label: 'Table Booking' },
        { value: 'delivery', label: 'Delivery' },
        { value: 'takeout', label: 'Takeout' },
      ];
    case 'beauty':
      return [
        { value: 'haircut', label: 'Haircut' },
        { value: 'coloring', label: 'Coloring' },
        { value: 'styling', label: 'Styling' },
      ];
    default:
      return [];
  }
});

const validate = (): boolean => {
  errors.value = {};
  
  if (!config.value.name) {
    errors.value.name = 'Name is required';
  }
  
  if (!config.value.service) {
    errors.value.service = 'Service is required';
  }
  
  const isValid = Object.keys(errors.value).length === 0;
  emit('validate', isValid);
  
  return isValid;
};

watch(config, (newConfig) => {
  emit('configChange', newConfig);
}, { deep: true });
</script>

<template>
  <div class="service-configurator">
    <Input
      v-model="config.name"
      label="Service Name"
      placeholder="Enter service name"
      :error="errors.name"
      required
      @blur="validate"
    />
    
    <Select
      v-model="config.service"
      label="Service Type"
      :options="serviceOptions"
      placeholder="Select service type"
      :error="errors.service"
      required
      @change="validate"
    />
    
    <Input
      v-model="config.description"
      label="Description"
      placeholder="Enter service description"
      type="textarea"
    />
    
    <Button variant="primary" @click="validate">
      Validate Configuration
    </Button>
  </div>
</template>
```

---

### Phase 4: Migration Execution (8-12 hours)

#### 4.1 Migration Script

**File:** `scripts/migrate-blade-to-vue.sh`

```bash
#!/bin/bash

# Blade to Vue Migration Script
# Usage: ./scripts/migrate-blade-to-vue.sh <blade-component-path>

BLADE_PATH=$1
VUE_DIR="frontend/src/components"

if [ -z "$BLADE_PATH" ]; then
    echo "Usage: $0 <blade-component-path>"
    exit 1
fi

# Extract component name
COMPONENT_NAME=$(basename "$BLADE_PATH" .blade.php)
COMPONENT_NAME_PASCAL=$(echo "$COMPONENT_NAME" | sed -r 's/(^|_)(\w)/\U\2/g')

# Determine target directory
if [[ $BLADE_PATH == *"configurators"* ]]; then
    TARGET_DIR="$VUE_DIR/configurators"
elif [[ $BLADE_PATH == *"ui"* ]]; then
    TARGET_DIR="$VUE_DIR/ui"
else
    TARGET_DIR="$VUE_DIR/business"
fi

# Create target directory if not exists
mkdir -p "$TARGET_DIR"

# Create Vue component
VUE_FILE="$TARGET_DIR/${COMPONENT_NAME_PASCAL}.vue"

echo "Migrating $BLADE_PATH to $VUE_FILE"

# Copy blade content for reference
cp "$BLADE_PATH" "${BLADE_PATH}.backup"

# Create Vue component template
cat > "$VUE_FILE" << EOF
<script setup lang="ts">
import { computed, type PropType } from 'vue';

interface ${COMPONENT_NAME_PASCAL}Props {
  // Add props here based on @props directive in Blade
}

const props = withDefaults(defineProps<${COMPONENT_NAME_PASCAL}Props>(), {
  // Add default values here
});

const emit = defineEmits<{
  // Add events here
}>();

// Add component logic here
</script>

<template>
  <div class="${COMPONENT_NAME}">
    <!-- Migrate Blade template here -->
  </div>
</template>

<style scoped>
.${COMPONENT_NAME} {
  /* Add styles here */
}
</style>
EOF

echo "Migration complete. Please update the Vue component with actual logic."
echo "Blade file backed up to: ${BLADE_PATH}.backup"
```

#### 4.2 Migration Checklist

For each Blade component:

- [ ] Analyze Blade component structure and dependencies
- [ ] Identify props (@props directive)
- [ ] Identify slots (@slot directive)
- [ ] Identify data passed from controller
- [ ] Create Vue component with TypeScript
- [ ] Migrate template syntax
- [ ] Convert Blade directives to Vue directives
- [ ] Add event handlers
- [ ] Add computed properties
- [ ] Add validation
- [ ] Add tests
- [ ] Update parent components to use Vue component
- [ ] Remove Blade component
- [ ] Test functionality

---

### Phase 5: Testing and Validation (4-6 hours)

#### 5.1 Vue Component Test Template

**File:** `tests/Unit/Vue/ComponentTest.spec.ts`

```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Button from '@/components/ui/Button.vue';

describe('Button Component', () => {
  it('renders with default props', () => {
    const wrapper = mount(Button, {
      slots: {
        default: 'Click me',
      },
    });

    expect(wrapper.text()).toContain('Click me');
    expect(wrapper.find('button').classes()).toContain('bg-blue-600');
  });

  it('emits click event when clicked', async () => {
    const wrapper = mount(Button, {
      slots: {
        default: 'Click me',
      },
    });

    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted('click')).toBeTruthy();
  });

  it('is disabled when disabled prop is true', () => {
    const wrapper = mount(Button, {
      props: {
        disabled: true,
      },
      slots: {
        default: 'Click me',
      },
    });

    expect(wrapper.find('button').attributes('disabled')).toBeDefined();
  });

  it('shows loading spinner when loading prop is true', () => {
    const wrapper = mount(Button, {
      props: {
        loading: true,
      },
      slots: {
        default: 'Click me',
      },
    });

    expect(wrapper.find('.animate-spin').exists()).toBe(true);
  });
});
```

---

## Migration Status Tracking

**File:** `docs/frontend/MIGRATION_STATUS.md`

```markdown
# Blade → Vue Migration Status

## Progress: 0/79 components (0%)

### Completed (0)
- None

### In Progress (0)
- None

### Pending (79)

#### Configurators (58)
- [ ] product-configurator
- [ ] service-configurator
- [ ] booking-configurator
- [ ] ... (55 more)

#### UI Components (12)
- [ ] button
- [ ] input
- [ ] select
- [ ] ... (9 more)

#### Other Components (9)
- [ ] header
- [ ] footer
- [ ] ... (7 more)
```

---

## Success Criteria

- [ ] All 79 Blade components migrated to Vue
- [ ] All Vue components have TypeScript types
- [ ] All Vue components have unit tests
- [ ] No Blade components remain in production
- [ ] UI Kit documented with Storybook
- [ ] Performance maintained or improved
- [ ] Accessibility standards met (WCAG 2.1 AA)
- [ ] All tests passing

---

## Next Steps

1. Execute Phase 1: Analysis and Planning (4-6 hours)
2. Execute Phase 2: Vue UI Kit Foundation (8-12 hours)
3. Execute Phase 3: Component Migration Templates (12-16 hours)
4. Execute Phase 4: Migration Execution (8-12 hours)
5. Execute Phase 5: Testing and Validation (4-6 hours)
6. Update COMPREHENSIVE_FIX_ROADMAP_2026.md with completion status

---

**Document Status:** 🔄 IN PROGRESS  
**Last Updated:** 25.04.2026  
**Next Review:** After Phase 2 completion
