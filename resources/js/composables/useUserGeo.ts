/**
 * useUserGeo — composable для геолокации пользователя
 * Определяет позицию, считает расстояние (Haversine), форматирует вывод.
 */
import { ref, readonly, type Ref, type DeepReadonly } from 'vue';

/* ── Типы ── */
export type GeoStatus = 'pending' | 'granted' | 'denied' | 'unavailable';
export type DeliveryMode = 'courier' | 'visit' | 'pickup';
export type ItemType = 'product' | 'service' | 'transport' | 'booking' | 'food' | 'event';

export interface GeoLabelResult {
    icon: string;
    text: string;
    short: string;
}

export interface GeoItem {
    lat?: number | null;
    lng?: number | null;
    distance?: number | null;
    [key: string]: unknown;
}

export interface UseUserGeoReturn {
    userLat: DeepReadonly<Ref<number | null>>;
    userLng: DeepReadonly<Ref<number | null>>;
    geoStatus: DeepReadonly<Ref<GeoStatus>>;
    geoError: DeepReadonly<Ref<string | null>>;
    calcDistance: (lat1: number, lng1: number, lat2: number, lng2: number) => number;
    formatDistance: (km: number | null) => string | null;
    geoLabel: (deliveryMode: DeliveryMode, distanceKm: number | null, type: ItemType) => GeoLabelResult | null;
    distanceToUser: (lat: number | null, lng: number | null) => number | null;
    enrichItemsWithDistance: <T extends GeoItem>(items: T[]) => (T & { distance: number | null })[];
}

/* ── Состояние (singleton — общий для всех компонентов) ── */
const userLat = ref<number | null>(null);
const userLng = ref<number | null>(null);
const geoStatus = ref<GeoStatus>('pending');
const geoError = ref<string | null>(null);

let requested = false;

/**
 * Haversine — расстояние между двумя точками в км
 */
function calcDistance(lat1: number, lng1: number, lat2: number, lng2: number): number {
    const R = 6371; // радиус Земли, км
    const toRad = (d: number): number => (d * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

/**
 * Форматирование расстояния
 * < 1 км → "850 м"
 * 1–99 км → "3.2 км"
 * ≥ 100 км → "120 км"
 */
function formatDistance(km: number | null): string | null {
    if (km == null) return null;
    if (km < 1) return `${Math.round(km * 1000)} м`;
    if (km < 100) return `${km.toFixed(1)} км`;
    return `${Math.round(km)} км`;
}

/**
 * Текст доставки/расстояния по типу вертикали
 */
function geoLabel(deliveryMode: DeliveryMode, distanceKm: number | null, type: ItemType): GeoLabelResult | null {
    const dist = formatDistance(distanceKm);

    if (type === 'transport') {
        if (distanceKm != null) {
            const minutes = Math.max(1, Math.round(distanceKm * 2));
            return { icon: '🚗', text: `${minutes} мин подача`, short: `${minutes} мин` };
        }
        return { icon: '🚗', text: 'Подача', short: 'Подача' };
    }

    if (deliveryMode === 'courier') {
        if (dist) return { icon: '🚚', text: `Курьер · ${dist}`, short: `🚚 ${dist}` };
        return { icon: '🚚', text: 'Курьерская доставка', short: '🚚 Доставка' };
    }

    if (deliveryMode === 'visit') {
        if (dist) return { icon: '📍', text: `${dist} от вас`, short: `📍 ${dist}` };
        return { icon: '📍', text: 'Посещение', short: '📍 Посещение' };
    }

    if (deliveryMode === 'pickup') {
        if (dist) return { icon: '🏪', text: `Самовывоз · ${dist}`, short: `🏪 ${dist}` };
        return { icon: '🏪', text: 'Самовывоз', short: '🏪 Самовывоз' };
    }

    // fallback
    if (dist) return { icon: '📍', text: dist, short: `📍 ${dist}` };
    return null;
}

/**
 * Запрос геолокации у браузера
 */
function requestGeo(): void {
    if (requested) return;
    requested = true;

    if (!navigator.geolocation) {
        geoStatus.value = 'unavailable';
        geoError.value = 'Geolocation API недоступен';
        // Fallback — Москва
        userLat.value = 55.7558;
        userLng.value = 37.6173;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos: GeolocationPosition) => {
            userLat.value = pos.coords.latitude;
            userLng.value = pos.coords.longitude;
            geoStatus.value = 'granted';
        },
        (err: GeolocationPositionError) => {
            geoStatus.value = 'denied';
            geoError.value = err.message;
            // Fallback — Москва
            userLat.value = 55.7558;
            userLng.value = 37.6173;
        },
        { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
    );
}

/**
 * Рассчитать расстояние от пользователя до точки
 */
function distanceToUser(lat: number | null, lng: number | null): number | null {
    if (userLat.value == null || userLng.value == null || lat == null || lng == null) return null;
    return calcDistance(userLat.value, userLng.value, lat, lng);
}

/**
 * Добавить distance к массиву items (создаёт новые объекты)
 */
function enrichItemsWithDistance<T extends GeoItem>(items: T[]): (T & { distance: number | null })[] {
    if (userLat.value == null) {
        return items.map((item) => ({ ...item, distance: null }));
    }
    return items.map((item) => ({
        ...item,
        distance: item.lat != null && item.lng != null
            ? calcDistance(userLat.value!, userLng.value!, item.lat, item.lng)
            : null,
    }));
}

/**
 * Composable hook
 */
export function useUserGeo(): UseUserGeoReturn {
    // Запрашиваем геолокацию при первом вызове
    requestGeo();

    return {
        userLat:   readonly(userLat),
        userLng:   readonly(userLng),
        geoStatus: readonly(geoStatus),
        geoError:  readonly(geoError),
        calcDistance,
        formatDistance,
        geoLabel,
        distanceToUser,
        enrichItemsWithDistance,
    };
}
