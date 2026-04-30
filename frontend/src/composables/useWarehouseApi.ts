/**
 * Warehouse API Composable
 * 
 * Provides reactive methods for interacting with Warehouse API endpoints
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */

import { ref } from 'vue';

interface Warehouse {
  id: string;
  name: string;
  address: string;
  branch_id: string | null;
  type: string;
  capacity: number;
  current_stock: number;
  utilization_percentage: number;
  is_active: boolean;
  created_at: string;
  updated_at: string | null;
}

interface Zone {
  id: string;
  warehouse_id: string;
  name: string;
  type: string;
  capacity: number;
  current_stock: number;
  utilization_percentage: number;
  branch_id: string | null;
  is_active: boolean;
  created_at: string;
}

interface Product {
  id: string;
  sku: string;
  name: string;
  barcode: string | null;
  description: string | null;
  category: string | null;
  brand: string | null;
  unit: string;
  weight: number;
  is_hazardous: boolean;
  is_fragile: boolean;
  requires_temperature_control: boolean;
  is_active: boolean;
}

interface Movement {
  id: string;
  warehouse_id: string;
  from_zone_id: string | null;
  to_zone_id: string | null;
  inventory_item_id: string;
  product_sku: string;
  quantity: number;
  movement_type: string;
  order_type: string | null;
  order_id: string | null;
  branch_id: string | null;
  reason: string | null;
  created_at: string;
}

interface CreateWarehouseRequest {
  name: string;
  address: string;
  branch_id?: string;
  type: string;
  capacity: number;
}

interface CreateZoneRequest {
  warehouse_id: string;
  name: string;
  type: string;
  capacity: number;
  branch_id?: string;
}

interface CreateProductRequest {
  sku: string;
  name: string;
  unit?: string;
  weight?: number;
  barcode?: string;
  description?: string;
  category?: string;
  brand?: string;
  is_hazardous?: boolean;
  is_fragile?: boolean;
  requires_temperature_control?: boolean;
  min_temperature?: number;
  max_temperature?: number;
  dimensions?: Record<string, any>;
}

interface CreateMovementRequest {
  warehouse_id: string;
  from_zone_id?: string;
  to_zone_id?: string;
  inventory_item_id: string;
  product_sku: string;
  quantity: number;
  movement_type: string;
  order_type?: string;
  order_id?: string;
  branch_id?: string;
  reason?: string;
}

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// Get auth token from localStorage
const getAuthToken = (): string => {
  return localStorage.getItem('auth_token') || '';
};

// Set auth token
const setAuthToken = (token: string): void => {
  localStorage.setItem('auth_token', token);
};

// Clear auth token
const clearAuthToken = (): void => {
  localStorage.removeItem('auth_token');
};

// API request wrapper with auth
const apiRequest = async <T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> => {
  const token = getAuthToken();
  const url = `${API_BASE_URL}${endpoint}`;

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Network error' }));
    throw new Error(error.message || `HTTP error! status: ${response.status}`);
  }

  return response.json();
};

export function useWarehouseApi() {
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Warehouse endpoints
  const getWarehouses = async (): Promise<Warehouse[]> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Warehouse[]>('/warehouses');
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch warehouses';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getActiveWarehouses = async (): Promise<Warehouse[]> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Warehouse[]>('/warehouses/active');
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch active warehouses';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getWarehouse = async (id: string): Promise<Warehouse> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Warehouse>(`/warehouses/${id}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch warehouse';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const createWarehouse = async (data: CreateWarehouseRequest): Promise<Warehouse> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Warehouse>('/warehouses', {
        method: 'POST',
        body: JSON.stringify(data),
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create warehouse';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const updateWarehouseStock = async (
    id: string,
    quantity: number
  ): Promise<Warehouse> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Warehouse>(`/warehouses/${id}/stock`, {
        method: 'PUT',
        body: JSON.stringify({ quantity }),
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update stock';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const activateWarehouse = async (id: string): Promise<Warehouse> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Warehouse>(`/warehouses/${id}/activate`, {
        method: 'POST',
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to activate warehouse';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deactivateWarehouse = async (id: string): Promise<Warehouse> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Warehouse>(`/warehouses/${id}/deactivate`, {
        method: 'POST',
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to deactivate warehouse';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deleteWarehouse = async (id: string): Promise<void> => {
    loading.value = true;
    error.value = null;
    try {
      await apiRequest<void>(`/warehouses/${id}`, {
        method: 'DELETE',
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete warehouse';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  // Zone endpoints
  const getZonesByWarehouse = async (warehouseId: string): Promise<Zone[]> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Zone[]>(`/zones/warehouse/${warehouseId}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch zones';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getActiveZonesByWarehouse = async (warehouseId: string): Promise<Zone[]> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Zone[]>(`/zones/warehouse/${warehouseId}/active`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch active zones';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getZone = async (id: string): Promise<Zone> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Zone>(`/zones/${id}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch zone';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const createZone = async (data: CreateZoneRequest): Promise<Zone> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Zone>('/zones', {
        method: 'POST',
        body: JSON.stringify(data),
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create zone';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const updateZoneStock = async (id: string, quantity: number): Promise<Zone> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Zone>(`/zones/${id}/stock`, {
        method: 'PUT',
        body: JSON.stringify({ quantity }),
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update zone stock';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deleteZone = async (id: string): Promise<void> => {
    loading.value = true;
    error.value = null;
    try {
      await apiRequest<void>(`/zones/${id}`, {
        method: 'DELETE',
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete zone';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  // Product endpoints
  const getActiveProducts = async (): Promise<Product[]> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Product[]>('/products');
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch products';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getProduct = async (id: string): Promise<Product> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Product>(`/products/${id}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch product';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getProductBySku = async (sku: string): Promise<Product> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Product>(`/products/sku/${sku}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch product by SKU';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getProductByBarcode = async (barcode: string): Promise<Product> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Product>(`/products/barcode/${barcode}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch product by barcode';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getProductsByCategory = async (category: string): Promise<Product[]> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Product[]>(`/products/category/${category}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch products by category';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const searchProducts = async (query: string): Promise<Product[]> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Product[]>(`/products/search/${encodeURIComponent(query)}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to search products';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const createProduct = async (data: CreateProductRequest): Promise<Product> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Product>('/products', {
        method: 'POST',
        body: JSON.stringify(data),
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create product';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deleteProduct = async (id: string): Promise<void> => {
    loading.value = true;
    error.value = null;
    try {
      await apiRequest<void>(`/products/${id}`, {
        method: 'DELETE',
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete product';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  // Movement endpoints
  const getMovementsByWarehouse = async (warehouseId: string, branchId?: string): Promise<Movement[]> => {
    loading.value = true;
    error.value = null;
    try {
      const url = branchId
        ? `/movements/warehouse/${warehouseId}?branch_id=${branchId}`
        : `/movements/warehouse/${warehouseId}`;
      return await apiRequest<Movement[]>(url);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch movements';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getMovementsByOrderType = async (orderType: string, branchId?: string): Promise<Movement[]> => {
    loading.value = true;
    error.value = null;
    try {
      const url = branchId
        ? `/movements/order-type/${orderType}?branch_id=${branchId}`
        : `/movements/order-type/${orderType}`;
      return await apiRequest<Movement[]>(url);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch movements by order type';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getMovementsByMovementType = async (movementType: string, branchId?: string): Promise<Movement[]> => {
    loading.value = true;
    error.value = null;
    try {
      const url = branchId
        ? `/movements/movement-type/${movementType}?branch_id=${branchId}`
        : `/movements/movement-type/${movementType}`;
      return await apiRequest<Movement[]>(url);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch movements by movement type';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getMovementsByInventoryItem = async (inventoryItemId: string): Promise<Movement[]> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Movement[]>(`/movements/inventory-item/${inventoryItemId}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch movements by inventory item';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getMovementsByDateRange = async (
    from: Date,
    to: Date,
    branchId?: string
  ): Promise<Movement[]> => {
    loading.value = true;
    error.value = null;
    try {
      const fromStr = from.toISOString().split('T')[0];
      const toStr = to.toISOString().split('T')[0];
      const url = branchId
        ? `/movements/date-range?from=${fromStr}&to=${toStr}&branch_id=${branchId}`
        : `/movements/date-range?from=${fromStr}&to=${toStr}`;
      return await apiRequest<Movement[]>(url);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch movements by date range';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const getMovement = async (id: string): Promise<Movement> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Movement>(`/movements/${id}`);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch movement';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const recordMovement = async (data: CreateMovementRequest): Promise<Movement> => {
    loading.value = true;
    error.value = null;
    try {
      return await apiRequest<Movement>('/movements', {
        method: 'POST',
        body: JSON.stringify(data),
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to record movement';
      throw e;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    // Auth token helpers
    setAuthToken,
    clearAuthToken,
    getAuthToken,
    // Warehouse methods
    getWarehouses,
    getActiveWarehouses,
    getWarehouse,
    createWarehouse,
    updateWarehouseStock,
    activateWarehouse,
    deactivateWarehouse,
    deleteWarehouse,
    // Zone methods
    getZonesByWarehouse,
    getActiveZonesByWarehouse,
    getZone,
    createZone,
    updateZoneStock,
    deleteZone,
    // Product methods
    getActiveProducts,
    getProduct,
    getProductBySku,
    getProductByBarcode,
    getProductsByCategory,
    searchProducts,
    createProduct,
    deleteProduct,
    // Movement methods
    getMovementsByWarehouse,
    getMovementsByOrderType,
    getMovementsByMovementType,
    getMovementsByInventoryItem,
    getMovementsByDateRange,
    getMovement,
    recordMovement,
  };
}
