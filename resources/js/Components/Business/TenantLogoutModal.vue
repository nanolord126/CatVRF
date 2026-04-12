<!--
  ╔═══════════════════════════════════════════════════════════════════════════╗
  ║  TenantLogoutModal.vue — модальное окно подтверждения выхода из системы  ║
  ║  CatVRF 2026 — B2B Tenant Dashboard · Vue 3 + TS · Tailwind v4         ║
  ║  Danger Modal — красный акцент · glassmorphism · ripple-lo              ║
  ║  Вертикали: beauty · taxi · food · hotel · realEstate · flowers ·       ║
  ║             fashion · furniture · fitness · travel · default             ║
  ╚═══════════════════════════════════════════════════════════════════════════╝
-->
<script setup lang="ts">
/**
 * TenantLogoutModal.vue — Подтверждение выхода из системы
 *
 * Danger-модалка:
 *   • Заголовок «Выйти из системы?»
 *   • Описание последствий (сессия, кэш, tenant-данные)
 *   • Инфо о текущем бизнесе (тенант, B2B/B2C, имя пользователя)
 *   • «Отмена» (ghost) / «Выйти» (danger, красная)
 *   • loading-spinner на кнопке выхода
 *   • ripple-эффект, glassmorphism, CSS Logical Properties
 *   • Полная адаптивность mobile → desktop → fullscreen
 *
 * После подтверждения:
 *   1. POST /logout  (CSRF)
 *   2. auth.logout()  (очистка стора)
 *   3. window.location.href = '/'  (полный редирект)
 */

import {
import { useAuth, useNotifications } from '@/stores'
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
} from 'vue'

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TYPES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

interface VerticalLogoutHint {
  label:     string
  icon:      string
  farewell:  string
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   PROPS & EMITS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const props = withDefaults(defineProps<{
  show?:       boolean
  vertical?:   string
  logoutUrl?:  string
  redirectUrl?: string
}>(), {
  show:        false,
  vertical:    'default',
  logoutUrl:   '/logout',
  redirectUrl: '/',
})

const emit = defineEmits<{
  'close':    []
  'confirm':  []
  'logout':   []
}>()

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STORES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const auth          = useAuth()
const notifications = useNotifications()

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   VERTICAL CONFIG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const VERTICAL_HINTS: Record<string, VerticalLogoutHint> = {
  beauty:     { label: 'Салон красоты',   icon: '💄', farewell: 'Ваши записи и мастера останутся на месте.' },
  taxi:       { label: 'Таксопарк',       icon: '🚕', farewell: 'Водители и заказы сохранены в системе.' },
  food:       { label: 'Ресторан',        icon: '🍽️', farewell: 'Меню и текущие заказы не будут затронуты.' },
  hotel:      { label: 'Отель',           icon: '🏨', farewell: 'Бронирования и номера останутся активными.' },
  realEstate: { label: 'Недвижимость',    icon: '🏢', farewell: 'Объекты и сделки сохранены в базе.' },
  flowers:    { label: 'Цветы',           icon: '💐', farewell: 'Каталог букетов и доставки не изменятся.' },
  fashion:    { label: 'Мода',            icon: '👗', farewell: 'Коллекции и AR-примерки сохранены.' },
  furniture:  { label: 'Мебель',          icon: '🛋️', farewell: 'Каталог и 3D-проекты останутся доступными.' },
  fitness:    { label: 'Фитнес',          icon: '💪', farewell: 'Абонементы и планы тренировок сохранены.' },
  travel:     { label: 'Туризм',          icon: '✈️', farewell: 'Туры и бронирования не будут отменены.' },
  default:    { label: 'Бизнес',          icon: '🏪', farewell: 'Все данные бизнеса сохранены в системе.' },
}

const hint = computed<VerticalLogoutHint>(() =>
  VERTICAL_HINTS[props.vertical] ?? VERTICAL_HINTS.default
)

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const isLoggingOut  = ref(false)
const hasError      = ref(false)
const errorMessage  = ref('')
const shakeActive   = ref(false)

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   COMPUTED
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

const displayName = computed(() =>
  auth.tenantName || auth.businessGroupName || auth.userName || 'Бизнес'
)

const modeLabel = computed(() =>
  auth.isB2BMode ? 'B2B' : 'B2C'
)

const consequences = computed<string[]>(() => [
  'Текущая сессия будет завершена',
  'Локальный кэш и токены авторизации будут очищены',
  `Вы будете перенаправлены на главную страницу`,
])

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ACTIONS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

async function confirmLogout() {
  if (isLoggingOut.value) return

  isLoggingOut.value = true
  hasError.value     = false
  errorMessage.value = ''

  emit('confirm')

  try {
    /* ── 1. POST /logout (CSRF) ── */
    const csrfToken =
      document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''

    const res = await fetch(props.logoutUrl, {
      method:  'POST',
      headers: {
        'Accept':           'application/json',
        'Content-Type':     'application/json',
        'X-CSRF-TOKEN':     csrfToken,
        'X-Correlation-ID': crypto.randomUUID(),
      },
      credentials: 'same-origin',
    })

    if (!res.ok && res.status !== 302 && res.status !== 419) {
      throw new Error(`Ошибка сервера: ${res.status}`)
    }

    /* ── 2. Очистка стора ── */
    auth.logout()
    emit('logout')

    /* ── 3. Полный редирект ── */
    window.location.href = props.redirectUrl

  } catch (err: unknown) {
    isLoggingOut.value = false
    hasError.value     = true
    errorMessage.value = err instanceof Error
      ? err.message
      : 'Не удалось выполнить выход. Попробуйте ещё раз.'

    /* shake-animation на карточке */
    shakeActive.value = true
    setTimeout(() => { shakeActive.value = false }, 500)
  }
}

function cancel() {
  if (isLoggingOut.value) return
  emit('close')
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   KEYBOARD — Esc to close
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.show && !isLoggingOut.value) {
    cancel()
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   RIPPLE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

function ripple(e: MouseEvent) {
  const target = e.currentTarget as HTMLElement
  const rect   = target.getBoundingClientRect()
  const d      = Math.max(rect.width, rect.height) * 2
  const el     = document.createElement('span')
  el.className = 'absolute rounded-full bg-white/15 pointer-events-none animate-[ripple-lo_0.6s_ease-out]'
  el.style.cssText = [
    `inline-size:${d}px`,
    `block-size:${d}px`,
    `inset-inline-start:${e.clientX - rect.left - d / 2}px`,
    `inset-block-start:${e.clientY - rect.top - d / 2}px`,
  ].join(';')
  target.appendChild(el)
  setTimeout(() => el.remove(), 650)
}
</script>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TEMPLATE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<template>
  <Transition name="overlay-lo">
    <div
      v-if="show"
      class="fixed inset-0 z-90 flex items-center justify-center p-4"
      @click.self="cancel"
    >
      <!-- ── backdrop ── -->
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancel" />

      <!-- ── modal card ── -->
      <Transition name="card-lo" appear>
        <div
          v-if="show"
          :class="[
            'relative z-10 inline-size-full max-w-md rounded-2xl border',
            'bg-(--t-surface)/80 backdrop-blur-xl shadow-2xl overflow-hidden',
            hasError
              ? 'border-rose-500/50'
              : 'border-(--t-border)',
            shakeActive ? 'animate-[shake-lo_0.4s_ease-in-out]' : '',
          ]"
        >
          <!-- ── danger glow strip ── -->
          <div class="absolute inset-inline-start-0 inset-block-start-0 inline-size-full block-size-1
                      bg-linear-to-r from-rose-500/60 via-rose-400/40 to-transparent" />

          <!-- ── content ── -->
          <div class="px-5 pt-6 pb-5 sm:px-6">

            <!-- Icon -->
            <div class="mx-auto w-14 h-14 rounded-full bg-rose-500/12
                        flex items-center justify-center mb-4 ring-1 ring-rose-500/20">
              <svg class="w-7 h-7 text-rose-400" fill="none" viewBox="0 0 24 24"
                   stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25
                         2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
              </svg>
            </div>

            <!-- Title -->
            <h2 class="text-center text-lg font-bold text-(--t-text)">
              Выйти из системы?
            </h2>

            <!-- Subtitle -->
            <p class="text-center text-xs text-(--t-text-3) mt-1.5 leading-relaxed max-w-xs mx-auto">
              Вы будете отключены от панели управления.
              Незавершённые действия не сохранятся.
            </p>

            <!-- ── current tenant card ── -->
            <div class="mt-4 rounded-xl border border-(--t-border)/60 bg-(--t-bg)/50 px-4 py-3
                        flex items-center gap-3">
              <!-- vertical icon -->
              <div class="shrink-0 w-10 h-10 rounded-lg bg-(--t-surface) border border-(--t-border)/40
                          flex items-center justify-center text-xl">
                {{ hint.icon }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-(--t-text) truncate">{{ displayName }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                        :class="auth.isB2BMode
                          ? 'bg-sky-500/12 text-sky-400'
                          : 'bg-emerald-500/12 text-emerald-400'"
                  >
                    {{ modeLabel }}
                  </span>
                  <span class="text-[10px] text-(--t-text-3)">{{ hint.label }}</span>
                </div>
              </div>
              <!-- online dot -->
              <span class="shrink-0 w-2.5 h-2.5 rounded-full bg-emerald-400 ring-2 ring-emerald-400/20" />
            </div>

            <!-- ── consequences list ── -->
            <ul class="mt-4 flex flex-col gap-1.5">
              <li
                v-for="(item, i) in consequences"
                :key="i"
                class="flex items-start gap-2 text-xs text-(--t-text-2)"
              >
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-rose-400/70" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73
                           0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697
                           16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span>{{ item }}</span>
              </li>
            </ul>

            <!-- ── farewell hint ── -->
            <p class="mt-3 text-[10px] text-(--t-text-3)/70 text-center italic leading-relaxed">
              {{ hint.farewell }}
            </p>

            <!-- ── error message ── -->
            <Transition name="fade-lo">
              <div
                v-if="hasError"
                class="mt-3 rounded-lg bg-rose-500/10 border border-rose-500/20 px-3 py-2
                       text-xs text-rose-300 text-center"
              >
                ⚠️ {{ errorMessage }}
              </div>
            </Transition>
          </div>

          <!-- ── footer / actions ── -->
          <div class="px-5 pb-5 sm:px-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center
                      gap-2 sm:justify-end">

            <!-- Cancel -->
            <button
              :disabled="isLoggingOut"
              class="relative overflow-hidden flex items-center justify-center gap-2
                     px-5 py-2.5 rounded-xl text-sm font-medium
                     border border-(--t-border) text-(--t-text-2)
                     hover:bg-(--t-surface) hover:text-(--t-text) hover:border-(--t-text-3)/30
                     active:scale-[0.97] transition-all duration-200
                     disabled:opacity-40 disabled:cursor-not-allowed"
              @click="cancel"
              @mousedown="ripple"
            >
              Отмена
            </button>

            <!-- Logout (danger) -->
            <button
              :disabled="isLoggingOut"
              :class="[
                'relative overflow-hidden flex items-center justify-center gap-2',
                'px-5 py-2.5 rounded-xl text-sm font-semibold',
                'transition-all duration-200 active:scale-[0.97]',
                'disabled:cursor-not-allowed',
                isLoggingOut
                  ? 'bg-rose-500/40 text-rose-200 cursor-wait'
                  : 'bg-rose-500 text-white hover:bg-rose-600 shadow-lg shadow-rose-500/20 hover:shadow-rose-500/30',
              ]"
              @click="confirmLogout"
              @mousedown="ripple"
            >
              <!-- spinner -->
              <svg
                v-if="isLoggingOut"
                class="animate-spin w-4 h-4 shrink-0"
                fill="none" viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>

              <!-- icon -->
              <svg
                v-else
                class="w-4 h-4 shrink-0"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25
                         2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
              </svg>

              <span>{{ isLoggingOut ? 'Выход…' : 'Выйти' }}</span>
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     STYLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->

<style scoped>
/* ── Ripple ── */
@keyframes ripple-lo {
  from { transform: scale(0); opacity: 0.4; }
  to   { transform: scale(1); opacity: 0; }
}

/* ── Shake (error) ── */
@keyframes shake-lo {
  0%, 100% { transform: translateX(0); }
  15%      { transform: translateX(-6px); }
  30%      { transform: translateX(5px); }
  45%      { transform: translateX(-4px); }
  60%      { transform: translateX(3px); }
  75%      { transform: translateX(-2px); }
}

/* ── Overlay fade ── */
.overlay-lo-enter-active,
.overlay-lo-leave-active {
  transition: opacity 0.25s ease;
}
.overlay-lo-enter-from,
.overlay-lo-leave-to {
  opacity: 0;
}

/* ── Card scale-in ── */
.card-lo-enter-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.card-lo-leave-active {
  transition: all 0.2s ease-in;
}
.card-lo-enter-from {
  opacity: 0;
  transform: scale(0.92) translateY(12px);
}
.card-lo-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(6px);
}

/* ── Fade (error banner) ── */
.fade-lo-enter-active,
.fade-lo-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.fade-lo-enter-from,
.fade-lo-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
