/**
 * CatVRF 2026 — Food Vertical Types
 * Типы для ресторанов, блюд, заказов
 */

export interface FoodRestaurant {
    id: number;
    name: string;
    description: string | null;
    logo_url: string | null;
    cover_url: string | null;
    address: string;
    rating: number;
    reviews_count: number;
    delivery_time_min: number;
    delivery_time_max: number;
    min_order: number;
    delivery_fee: number;
    cuisine_types: string[];
    is_open: boolean;
    working_hours: Record<string, { open: string; close: string }>;
    tags: string[];
}

export interface FoodDish {
    id: number;
    restaurant_id: number;
    name: string;
    description: string | null;
    image_url: string | null;
    price: number;
    price_b2b: number | null;
    category: string;
    weight_grams: number | null;
    calories: number | null;
    proteins: number | null;
    fats: number | null;
    carbs: number | null;
    allergens: string[];
    is_vegetarian: boolean;
    is_vegan: boolean;
    is_available: boolean;
    preparation_time_min: number;
}

export interface FoodOrder {
    id: number;
    restaurant_id: number;
    restaurant_name: string;
    customer_name: string;
    customer_phone: string;
    delivery_address: string;
    items: FoodOrderItem[];
    status: 'pending' | 'accepted' | 'preparing' | 'ready' | 'delivering' | 'delivered' | 'cancelled';
    subtotal: number;
    delivery_fee: number;
    total: number;
    payment_method: 'card' | 'cash' | 'wallet';
    estimated_delivery: string | null;
    courier_id: number | null;
    notes: string | null;
    created_at: string;
    delivered_at: string | null;
}

export interface FoodOrderItem {
    dish_id: number;
    dish_name: string;
    quantity: number;
    price: number;
    total: number;
    notes: string | null;
}

export interface FoodNutritionPlan {
    calories_min: number;
    calories_max: number;
    diet: string;
    allergens: string[];
    recipes: FoodRecipe[];
}

export interface FoodRecipe {
    name: string;
    description: string;
    ingredients: { name: string; amount: string }[];
    calories: number;
    proteins: number;
    fats: number;
    carbs: number;
    preparation_time_min: number;
    steps: string[];
    image_url: string | null;
}

export interface FoodDeliveryStats {
    today_orders: number;
    today_revenue: number;
    average_delivery_time: number;
    average_rating: number;
    popular_dishes: { name: string; count: number }[];
}
