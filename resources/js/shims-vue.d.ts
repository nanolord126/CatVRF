/**
 * Shim для .vue файлов — позволяет TypeScript импортировать SFC-компоненты
 * без ошибок «Не удалось найти файл объявления модуля».
 */
declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
  export default component
}

/**
 * Shim для .js store-файлов (Pinia-like composables)
 * Позволяет TypeScript импортировать JS-store без ошибок.
 */
declare module '*.js' {
  const value: any
  export default value
  export const useBusinessStore: any
  export const useAuthStore: any
  export const useNotificationStore: any
}
