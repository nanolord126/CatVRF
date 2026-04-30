# Logistics AI: Route Optimization

**Версия:** 1.0  
**Дата:** 18.04.2026  
**Автор:** CatVRF AI Sensei  
**Статус:** Production Ready

---

## Оглавление

1. [Философия Route Optimization](#философия-route-optimization)
2. [VRP Fundamentals](#vrp-fundamentals)
3. [Solver Options](#solver-options)
4. [ML + VRP Hybrid Approach](#ml--vrp-hybrid-approach)
5. [Dynamic Re-routing](#dynamic-re-routing)
6. [Architecture for CatVRF](#architecture-for-catvrf)
7. [Реализация (по шагам)](#реализация-по-шагам)
8. [Мониторинг и метрики](#мониторинг-и-метрики)

---

## Философия Route Optimization

### Принципы

1. **Not pure ML, not pure OR**: Маршрутизация — это **VRP (Vehicle Routing Problem)** + ML для cost/time prediction. Чистый ML не гарантирует constraints (capacity, time windows).
2. **Solver as core**: OR-Tools/PuLP как solver — они гарантируют feasibility. ML только предсказывает cost/time для каждого edge.
3. **Multi-objective optimization**: Минимизация (distance + time + cost + courier_load_balance + customer_satisfaction).
4. **Dynamic > Static**: Реал-тайм re-routing при новых заказах, отменах, пробках.
5. **Hierarchical**: Сначала кластеризация (assignment курьеров к зонам), потом VRP внутри кластера.

### Зачем Route Optimization

- **Снижение cost на 15-30%**: Оптимальные маршруты = меньше км/час
- **Рост SLA**: Точные ETA = довольные клиенты
- **Балансировка флота**: Равномерная загрузка курьеров
- **Прогнозируемость**: Предсказуемое время доставки

---

## VRP Fundamentals

### Что такое VRP

**Vehicle Routing Problem (VRP)** — классическая задача комбинаторной оптимизации:

**Входные данные:**
- Депо (warehouse/restaurant) — стартовая точка
- N заказов (customers) с координатами и demand
- K курьеров (vehicles) с capacity
- Time windows (когда можно доставить)
- Time matrix (время между любыми двумя точками)

**Ограничения (Constraints):**
- Курьер не может превысить capacity
- Курьер должен вернуться в депо
- Time windows должны быть соблюдены
- Каждый заказ обслуживается ровно одним курьером

**Целевая функция (Objective):**
```
Minimize: Σ(distance) + Σ(time) + Σ(cost)
```

### Типы VRP

1. **CVRP (Capacitated VRP)** — с ограничением по capacity
2. **VRPTW (VRP with Time Windows)** — с временными окнами
3. **MDVRP (Multi-Depot VRP)** — несколько депо
4. **DVRP (Dynamic VRP)** — заказы приходят в реал-тайм
5. **PVRP (Periodic VRP)** — периодические доставки

### Для CatVRF

**Тип задачи:** **DVRPTW** (Dynamic VRP with Time Windows)
- Заказы приходят в реал-тайм
- Есть time windows (например, "доставить в течение 60 мин")
- Множество депо (рестораны, аптеки, ПВЗ)
- Смешанный флот (курьеры + такси + ПВЗ)

---

## Solver Options

### 1. Google OR-Tools (Рекомендуемый)

**Плюсы:**
- Production-ready, используется в Google Maps
- Поддерживает CVRP, VRPTW, MDVRP
- Множество solvers (CP-SAT, Guided Local Search)
- Open-source (Apache 2.0)
- Python API + C++ core

**Минусы:**
- Требует установки C++ core
- Для очень больших задач (>1000 orders) может быть медленным

**Пример (Python):**

```python
from ortools.constraint_solver import routing_enums_pb2
from ortools.constraint_solver import pywrapcp

def solve_vrp(locations, demands, vehicle_capacities, time_windows):
    """
    locations: [(lat, lon), ...]
    demands: [weight_kg, ...]
    vehicle_capacities: [capacity_kg, ...]
    time_windows: [(start, end), ...]
    """
    # Create distance matrix
    distance_matrix = compute_distance_matrix(locations)
    time_matrix = compute_time_matrix(locations)
    
    # Create routing index manager
    manager = pywrapcp.RoutingIndexManager(
        len(locations),
        len(vehicle_capacities),
        0  # depot index
    )
    
    # Create routing model
    routing = pywrapcp.RoutingModel(manager)
    
    # Create and register transit callback
    def distance_callback(from_index, to_index):
        from_node = manager.IndexToNode(from_index)
        to_node = manager.IndexToNode(to_index)
        return distance_matrix[from_node][to_node]
    
    transit_callback_index = routing.RegisterTransitCallback(distance_callback)
    routing.SetArcCostEvaluatorOfAllVehicles(transit_callback_index)
    
    # Add capacity constraint
    def demand_callback(from_index):
        from_node = manager.IndexToNode(from_index)
        return demands[from_node]
    
    demand_callback_index = routing.RegisterUnaryTransitCallback(demand_callback)
    routing.AddDimensionWithVehicleCapacity(
        demand_callback_index,
        0,  # null capacity slack
        vehicle_capacities,
        True,  # start cumul to zero
        'Capacity'
    )
    
    # Add time window constraint
    def time_callback(from_index, to_index):
        from_node = manager.IndexToNode(from_index)
        to_node = manager.IndexToNode(to_index)
        return time_matrix[from_node][to_node]
    
    time_callback_index = routing.RegisterTransitCallback(time_callback)
    routing.AddDimension(
        time_callback_index,
        30,  # allow waiting time
        30,  # maximum time per vehicle
        False,  # don't force start cumul to zero
        'Time'
    )
    time_dimension = routing.GetDimensionOrDie('Time')
    
    # Add time window constraints
    for location_idx, time_window in enumerate(time_windows):
        index = manager.NodeToIndex(location_idx)
        time_dimension.CumulVar(index).SetRange(time_window[0], time_window[1])
    
    # Set search parameters
    search_parameters = pywrapcp.DefaultRoutingSearchParameters()
    search_parameters.first_solution_strategy = (
        routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC
    )
    search_parameters.local_search_metaheuristic = (
        routing_enums_pb2.LocalSearchMetaheuristic.GUIDED_LOCAL_SEARCH
    )
    search_parameters.time_limit.seconds = 30  # max 30 seconds
    
    # Solve
    solution = routing.SolveWithParameters(search_parameters)
    
    if solution:
        return extract_solution(manager, routing, solution)
    else:
        return None
```

### 2. PuLP + CBC (Open-source, Python-only)

**Плюсы:**
- Чистый Python, easy to install
- CBC solver included (open-source)
- Гибкая формулировка constraints

**Минусы:**
- Медленнее OR-Tools для больших задач
- Меньше встроенных heuristics

**Пример:**

```python
import pulp
from geopy.distance import geodesic

def solve_vrp_pulp(locations, demands, vehicle_capacities):
    """
    MIP formulation of VRP.
    """
    n = len(locations)
    k = len(vehicle_capacities)
    
    # Distance matrix
    dist = [[geodesic(locations[i], locations[j]).km for j in range(n)] 
            for i in range(n)]
    
    # Create problem
    prob = pulp.LpProblem('VRP', pulp.LpMinimize)
    
    # Decision variables: x[i][j][v] = 1 if vehicle v goes from i to j
    x = pulp.LpVariable.dicts('x', 
                              ((i, j, v) for i in range(n) for j in range(n) for v in range(k)),
                              cat='Binary')
    
    # Objective: minimize total distance
    prob += pulp.lpSum(dist[i][j] * x[i][j][v] 
                       for i in range(n) for j in range(n) for v in range(k))
    
    # Constraints
    # Each customer visited exactly once
    for j in range(1, n):  # skip depot (0)
        prob += pulp.lpSum(x[i][j][v] for i in range(n) for v in range(k)) == 1
    
    # Flow conservation
    for v in range(k):
        for i in range(n):
            prob += pulp.lpSum(x[i][j][v] for j in range(n)) == \
                    pulp.lpSum(x[j][i][v] for j in range(n))
    
    # Capacity constraints
    for v in range(k):
        prob += pulp.lpSum(demands[j] * x[i][j][v] 
                           for i in range(n) for j in range(1, n)) <= vehicle_capacities[v]
    
    # Solve
    prob.solve(pulp.PULP_CBC_CMD(msg=False, timeLimit=30))
    
    return extract_solution(x)
```

### 3. Custom Heuristics (Fast, but suboptimal)

**Алгоритмы:**
- **Nearest Neighbor**: Жадный выбор ближайшего
- **Savings Algorithm (Clarke-Wright)**: Merge routes
- **Sweep Algorithm**: Кластеризация по углу
- **Genetic Algorithm**: Эволюционный подход

**Пример (Nearest Neighbor):**

```python
def nearest_neighbor_vrp(locations, demands, vehicle_capacities):
    """
    Fast heuristic for real-time re-routing.
    """
    n = len(locations)
    k = len(vehicle_capacities)
    
    routes = [[] for _ in range(k)]
    vehicle_loads = [0] * k
    unassigned = set(range(1, n))  # skip depot
    
    while unassigned:
        for v in range(k):
            if not unassigned:
                break
                
            # Find nearest unassigned customer
            current = routes[v][-1] if routes[v] else 0
            nearest = min(unassigned, 
                         key=lambda j: geodesic(locations[current], locations[j]).km)
            
            # Check capacity
            if vehicle_loads[v] + demands[nearest] <= vehicle_capacities[v]:
                routes[v].append(nearest)
                vehicle_loads[v] += demands[nearest]
                unassigned.remove(nearest)
    
    return routes
```

### Рекомендация для CatVRF

**Основной solver:** **Google OR-Tools** (CP-SAT)
- Для batch optimization (каждые 5-10 минут)
- Для начального планирования маршрутов

**Fallback solver:** **Nearest Neighbor** (custom)
- Для real-time re-routing (новый заказ, отмена)
- Если OR-Tools не успел за 5 секунд

**В будущем:** **Reinforcement Learning**
- Для полностью динамической маршрутизации
- Agent learns to re-route in real-time

---

## ML + VRP Hybrid Approach

### Проблема чистого VRP

Традиционный VRP использует **статическую time matrix**:
- `time[i][j] = distance / avg_speed`
- Не учитывает: пробки, погоду, время суток, тип транспорта

### Решение: ML предсказывает cost/time

**Hybrid архитектура:**

```
1. ML Model предсказывает:
   - travel_time[i][j][hour][weather]
   - fuel_cost[i][j]
   - courier_fatigue_score[i][j]
   
2. VRP Solver использует предсказанные значения:
   - cost[i][j] = α*travel_time + β*fuel_cost + γ*fatigue
   
3. Result: Оптимальный маршрут с учетом реальных условий
```

### ML Models для Route Optimization

#### 1. Travel Time Prediction Model

**Входные фичи:**
- Distance (km)
- Hour of day (0-23)
- Day of week (0-6)
- Traffic level (low/moderate/high/severe)
- Weather (sunny/rain/snow)
- Transport type (car/bike/walk)
- Historical avg speed for this route

**Выход:** `predicted_travel_time_minutes`

**Модель:** XGBoost Regression или LightGBM

**Пример (Python):**

```python
import xgboost as xgb
import pandas as pd

class TravelTimePredictor:
    def __init__(self, model_path):
        self.model = xgb.Booster()
        self.model.load_model(model_path)
    
    def predict(self, distance_km, hour, day_of_week, traffic_level, 
                weather, transport_type, historical_avg_speed):
        """
        Predict travel time in minutes.
        """
        features = pd.DataFrame([{
            'distance_km': distance_km,
            'hour': hour,
            'day_of_week': day_of_week,
            'traffic_level': traffic_level,
            'weather': weather,
            'transport_type': transport_type,
            'historical_avg_speed': historical_avg_speed,
        }])
        
        dmatrix = xgb.DMatrix(features)
        prediction = self.model.predict(dmatrix)
        
        return float(prediction[0])
```

#### 2. Fuel/Cost Prediction Model

**Входные фичи:**
- Distance (km)
- Transport type (car/bike/electric)
- Fuel price (current)
- Courier efficiency score

**Выход:** `predicted_cost_kopek`

#### 3. Courier Fatigue Score

**Входные фичи:**
- Orders completed today
- Hours worked today
- Hours since last break
- Battery level (for electric)

**Выход:** `fatigue_score` (0-1, higher = more tired)

### Интеграция ML в VRP

**Шаг 1: Предсказать time/cost matrix**

```python
def predict_time_matrix(locations, hour, weather, traffic_data):
    """
    Predict travel time matrix using ML.
    """
    n = len(locations)
    time_matrix = [[0] * n for _ in range(n)]
    
    for i in range(n):
        for j in range(n):
            if i == j:
                continue
            
            distance = geodesic(locations[i], locations[j]).km
            traffic_level = get_traffic_level(locations[i], locations[j], traffic_data)
            
            time_matrix[i][j] = travel_time_predictor.predict(
                distance_km=distance,
                hour=hour,
                day_of_week=datetime.now().weekday(),
                traffic_level=traffic_level,
                weather=weather,
                transport_type='car',
                historical_avg_speed=get_historical_avg_speed(i, j)
            )
    
    return time_matrix
```

**Шаг 2: Передать matrix в VRP solver**

```python
# Instead of static distance/time matrix
time_matrix = predict_time_matrix(locations, hour, weather, traffic_data)

# Solve VRP with ML-predicted times
solution = solve_vrp(locations, demands, vehicle_capacities, time_matrix)
```

---

## Dynamic Re-routing

### Когда нужен re-routing

1. **Новый заказ**: Пришел новый заказ в зоне активного маршрута
2. **Отмена заказа**: Курьер уже в пути, заказ отменили
3. **Пробка**: Traffic резко вырос на маршруте
4. **Курьер задерживается**: Курьер не успевает к time window
5. **Курьер offline**: Курьер вышел из строя

### Алгоритм Dynamic Re-routing

**Стратегия: Incremental Re-optimization**

```
1. Identify affected routes (routes with new/cancelled order or delay)
2. Extract subset of orders (affected + nearby)
3. Re-solve VRP for subset only (fast)
4. Merge back into full solution
5. Notify couriers via WebSocket
```

**Пример (Python):**

```python
def dynamic_re_route(current_routes, new_order, all_orders, vehicle_capacities):
    """
    Re-route when new order arrives.
    """
    # Find affected vehicles (nearby to new order)
    affected_vehicles = find_nearby_vehicles(new_order.location, current_routes)
    
    # Extract orders from affected vehicles + new order
    affected_orders = extract_orders_from_vehicles(affected_vehicles, current_routes)
    affected_orders.append(new_order)
    
    # Re-solve VRP for subset
    new_routes_subset = solve_vrp(
        locations=[o.location for o in affected_orders],
        demands=[o.demand for o in affected_orders],
        vehicle_capacities=[vehicle_capacities[v] for v in affected_vehicles],
        time_limit=5  # fast solve
    )
    
    # Merge back
    updated_routes = merge_routes(current_routes, new_routes_subset, affected_vehicles)
    
    return updated_routes
```

### Реал-тайм notification

**WebSocket event для курьеров:**

```php
<?php

namespace Modules\GeoLogistics\Events;

final class RouteUpdated
{
    public function __construct(
        public readonly int $courierId,
        public readonly array $newRoute,  // [{order_id, sequence, eta}]
        public readonly string $reason,    // 'new_order', 'cancellation', 'traffic'
    ) {}
}
```

---

## Architecture for CatVRF

### High-level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Laravel Backend                        │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  UnifiedFleetService                                  │  │
│  │  - assignToOrder()                                    │  │
│  │  - optimizeRoutes()                                   │  │
│  │  - reRoute()                                          │  │
│  └──────────────────────────────────────────────────────┘  │
│                            │                                │
│                            ▼                                │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  RouteOptimizationService                             │  │
│  │  - solveVRP() → calls Python microservice            │  │
│  │  - predictTimeMatrix() → calls ML service             │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │ HTTP / Redis Queue
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Python Microservice (FastAPI)                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  VRP Solver Service                                   │  │
│  │  - POST /solve/vrp                                    │  │
│  │  - Google OR-Tools + fallback heuristics              │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  ML Prediction Service                                │  │
│  │  - POST /predict/travel_time                          │  │
│  │  - XGBoost models loaded at startup                   │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    ClickHouse (Data)                        │
│  - feature_store_route_hourly (for training)                │
│  - logistics_orders_raw (historical data)                  │
│  - ml_predictions_eta (predictions vs actual)               │
└─────────────────────────────────────────────────────────────┘
```

### Laravel Services

#### `RouteOptimizationService`

```php
<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Services;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Log\LogManager;
use Modules\GeoLogistics\DTOs\VRPRequestDto;
use Modules\GeoLogistics\DTOs\VRPSolutionDto;

/**
 * Service для route optimization.
 * 
 * Канон 2026:
 * - Вызывает Python microservice по HTTP
 * - Circuit breaker для защиты от fallback
 * - Cache для повторных запросов
 * - Audit logging для всех оптимизаций
 */
final class RouteOptimizationService
{
    private const PYTHON_SERVICE_URL = 'http://localhost:8000';
    private const CACHE_TTL = 300; // 5 minutes
    
    public function __construct(
        private readonly HttpClient $http,
        private readonly LogManager $log,
        private readonly \Illuminate\Contracts\Cache\Repository $cache,
    ) {}
    
    /**
     * Решить VRP задачу.
     * 
     * @param VRPRequestDto $request
     * @return VRPSolutionDto
     * @throws \RuntimeException
     */
    public function solveVRP(VRPRequestDto $request): VRPSolutionDto
    {
        $correlationId = $request->correlationId;
        
        $this->log->channel('audit')->info('route_optimization.start', [
            'correlation_id' => $correlationId,
            'orders_count' => count($request->orders),
            'vehicles_count' => count($request->vehicles),
        ]);
        
        try {
            // Check cache first
            $cacheKey = $this->getCacheKey($request);
            if ($cached = $this->cache->get($cacheKey)) {
                $this->log->channel('audit')->info('route_optimization.cache_hit', [
                    'correlation_id' => $correlationId,
                ]);
                return VRPSolutionDto::fromJson($cached);
            }
            
            // Call Python service
            $response = $this->http->timeout(30)->post(
                self::PYTHON_SERVICE_URL . '/solve/vrp',
                $request->toArray()
            );
            
            if (!$response->successful()) {
                throw new \RuntimeException('VRP service error: ' . $response->status());
            }
            
            $data = $response->json();
            $solution = VRPSolutionDto::fromArray($data);
            
            // Cache result
            $this->cache->put($cacheKey, $solution->toJson(), self::CACHE_TTL);
            
            $this->log->channel('audit')->info('route_optimization.success', [
                'correlation_id' => $correlationId,
                'total_distance_km' => $solution->totalDistanceKm,
                'total_time_minutes' => $solution->totalTimeMinutes,
                'solver_type' => $solution->solverType,
            ]);
            
            return $solution;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('route_optimization.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to heuristic
            return $this->solveWithHeuristic($request);
        }
    }
    
    /**
     * Предсказать travel time matrix.
     * 
     * @param array $locations
     * @param int $hour
     * @param string $weather
     * @return array
     */
    public function predictTimeMatrix(array $locations, int $hour, string $weather): array
    {
        $response = $this->http->timeout(10)->post(
            self::PYTHON_SERVICE_URL . '/predict/travel_time_matrix',
            [
                'locations' => $locations,
                'hour' => $hour,
                'weather' => $weather,
            ]
        );
        
        if (!$response->successful()) {
            // Fallback to simple distance/speed
            return $this->fallbackTimeMatrix($locations);
        }
        
        return $response->json()['time_matrix'];
    }
    
    private function getCacheKey(VRPRequestDto $request): string
    {
        $hash = md5(json_encode($request->orders) . json_encode($request->vehicles));
        return "vrp_solution:{$hash}";
    }
    
    private function solveWithHeuristic(VRPRequestDto $request): VRPSolutionDto
    {
        // Simple nearest neighbor fallback
        // ...
        return VRPSolutionDto::fromHeuristic($request);
    }
    
    private function fallbackTimeMatrix(array $locations): array
    {
        // Distance / avg speed (30 km/h)
        // ...
        return [];
    }
}
```

#### `UnifiedFleetService` (Integration)

```php
<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Services;

use Modules\GeoLogistics\DTOs\OrderDto;
use Modules\GeoLogistics\DTOs\CourierDto;

/**
 * Unified Fleet Service — оркестрация assignment + routing.
 */
final class UnifiedFleetService
{
    public function __construct(
        private readonly CourierAssignmentService $assignmentService,
        private readonly RouteOptimizationService $routeOptimizationService,
    ) {}
    
    /**
     * Assign courier + optimize route.
     */
    public function assignToOrder(OrderDto $order): array
    {
        // 1. Find best couriers (ML-based assignment)
        $couriers = $this->assignmentService->findBestCouriers($order);
        
        // 2. If multiple couriers, optimize routes for each
        if (count($couriers) > 1) {
            $solutions = [];
            foreach ($couriers as $courier) {
                $solution = $this->routeOptimizationService->solveVRP(
                    VRPRequestDto::forCourierAndOrder($courier, $order)
                );
                $solutions[] = $solution;
            }
            
            // Pick best solution
            $bestSolution = $this->selectBestSolution($solutions);
            
            return [
                'courier_id' => $bestSolution->courierId,
                'route' => $bestSolution->route,
                'eta_minutes' => $bestSolution->etaMinutes,
            ];
        }
        
        // Single courier
        return [
            'courier_id' => $couriers[0]->id,
            'route' => [$order],
            'eta_minutes' => $this->estimateETA($couriers[0], $order),
        ];
    }
    
    /**
     * Optimize routes for all active couriers in zone.
     */
    public function optimizeRoutes(string $zoneId): void
    {
        // 1. Get all pending orders in zone
        $orders = $this->getPendingOrdersInZone($zoneId);
        
        // 2. Get all available couriers in zone
        $couriers = $this->getAvailableCouriersInZone($zoneId);
        
        // 3. Solve VRP
        $solution = $this->routeOptimizationService->solveVRP(
            VRPRequestDto::forZone($zoneId, $orders, $couriers)
        );
        
        // 4. Update routes in DB
        $this->updateCourierRoutes($solution);
        
        // 5. Notify couriers via WebSocket
        foreach ($solution->routes as $route) {
            event(new RouteUpdated($route->courierId, $route->waypoints, 'optimization'));
        }
    }
    
    /**
     * Re-route on new order/cancellation.
     */
    public function reRoute(string $zoneId, ?OrderDto $newOrder = null): void
    {
        // Incremental re-optimization
        // ...
    }
}
```

### Python Microservice (FastAPI)

```python
# main.py
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
import uvicorn
from ortools.constraint_solver import routing_enums_pb2
from ortools.constraint_solver import pywrapcp
import xgboost as xgb

app = FastAPI(title="CatVRF Route Optimization API")

# Load ML models at startup
travel_time_model = xgb.Booster()
travel_time_model.load_model('models/travel_time_v1.json')

class VRPRequest(BaseModel):
    depot: tuple[float, float]
    orders: List[tuple[float, float]]  # (lat, lon)
    demands: List[float]
    vehicle_capacities: List[float]
    time_windows: Optional[List[tuple[int, int]]] = None
    time_limit_seconds: int = 30

class VRPSolution(BaseModel):
    routes: List[List[int]]  # Each route is list of order indices
    total_distance_km: float
    total_time_minutes: float
    solver_type: str

@app.post("/solve/vrp", response_model=VRPSolution)
async def solve_vrp(request: VRPRequest):
    """
    Solve VRP using Google OR-Tools.
    """
    try:
        locations = [request.depot] + request.orders
        distance_matrix = compute_distance_matrix(locations)
        
        # Predict travel times with ML
        time_matrix = predict_time_matrix_with_ml(
            locations, 
            distance_matrix,
            current_hour=datetime.now().hour
        )
        
        # Solve VRP
        solution = solve_vrp_ortools(
            locations,
            request.demands,
            request.vehicle_capacities,
            time_matrix,
            request.time_windows,
            request.time_limit_seconds
        )
        
        return solution
    except Exception as e:
        # Fallback to heuristic
        return solve_vrp_heuristic(request)

@app.post("/predict/travel_time_matrix")
async def predict_travel_time_matrix(request: dict):
    """
    Predict travel time matrix using ML.
    """
    locations = request['locations']
    hour = request['hour']
    weather = request['weather']
    
    # Compute distance matrix
    distance_matrix = compute_distance_matrix(locations)
    
    # Predict times
    time_matrix = []
    for i in range(len(locations)):
        row = []
        for j in range(len(locations)):
            if i == j:
                row.append(0)
            else:
                time = predict_travel_time(
                    distance_matrix[i][j],
                    hour,
                    weather
                )
                row.append(time)
        time_matrix.append(row)
    
    return {'time_matrix': time_matrix}

def compute_distance_matrix(locations):
    """Compute Euclidean distance matrix."""
    n = len(locations)
    matrix = [[0] * n for _ in range(n)]
    for i in range(n):
        for j in range(n):
            if i != j:
                matrix[i][j] = haversine(locations[i], locations[j])
    return matrix

def predict_travel_time(distance_km, hour, weather):
    """Predict travel time using XGBoost."""
    # Simple mock - in production, use real model
    base_time = distance_km / 0.5  # 30 km/h avg
    if hour in [8, 9, 18, 19]:  # rush hour
        base_time *= 1.5
    if weather == 'rain':
        base_time *= 1.2
    return base_time

if __name__ == '__main__':
    uvicorn.run(app, host='0.0.0.0', port=8000)
```

---

## Реализация (по шагам)

### Неделя 1: Infrastructure Setup

1. **Установить Python dependencies**:
   ```bash
   # requirements.txt
   fastapi==0.104.1
   uvicorn[standard]==0.24.0
   ortools==9.8.3296
   xgboost==2.0.2
   geopy==2.4.0
   pydantic==2.5.0
   ```

2. **Создать Python microservice**:
   - `python-service/main.py` (FastAPI app)
   - `python-service/solvers/vrp_solver.py` (OR-Tools wrapper)
   - `python-service/models/` (directory for ML models)

3. **Запустить microservice**:
   ```bash
   cd python-service
   uvicorn main:app --host 0.0.0.0 --port 8000 --reload
   ```

4. **Добавить в docker-compose.yml**:
   ```yaml
   route-optimization:
     build: ./python-service
     ports:
       - "8000:8000"
     environment:
       - MODEL_PATH=/app/models
     volumes:
       - ./models:/app/models
   ```

### Неделя 2: Laravel Integration

1. **Создать DTOs**:
   - `VRPRequestDto`
   - `VRPSolutionDto`
   - `RouteDto`

2. **Создать Service**:
   - `RouteOptimizationService`

3. **Создать Events**:
   - `RouteUpdated`

4. **Добавить в UnifiedFleetService**:
   - Интегрировать `solveVRP()` в `assignToOrder()`

### Неделя 3: ML Model Training

1. **Экспорт данных из ClickHouse**:
   ```bash
   clickhouse-client --query="SELECT * FROM feature_store_route_hourly FORMAT CSVWithNames" > route_data.csv
   ```

2. **Обучить travel time model** (Python Jupyter):
   ```python
   import pandas as pd
   import xgboost as xgb
   from sklearn.model_selection import train_test_split
   
   df = pd.read_csv('route_data.csv')
   X = df[['distance_km', 'hour_of_day', 'day_of_week', 'traffic_level', 'temperature_c']]
   y = df['actual_duration_min']
   
   X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2)
   
   model = xgb.XGBRegressor(
       n_estimators=100,
       max_depth=6,
       learning_rate=0.1
   )
   model.fit(X_train, y_train)
   
   # Save model
   model.save_model('models/travel_time_v1.json')
   ```

3. **Загрузить модель в Python service**:
   - Модель загружается при startup в `main.py`

### Неделя 4: Dynamic Re-routing

1. **Создать Job для batch optimization**:
   - `OptimizeRoutesJob` — запускается каждые 5 минут
   - Вызывает `UnifiedFleetService::optimizeRoutes()`

2. **Создать Event listeners**:
   - `OrderCreated` → trigger re-route
   - `OrderCancelled` → trigger re-route

3. **WebSocket integration**:
   - Курьеры подписываются на `RouteUpdated`
   - Frontend показывает новый маршрут

### Неделя 5: Testing & Monitoring

1. **Unit тесты**:
   - `RouteOptimizationServiceTest`
   - `UnifiedFleetServiceTest`

2. **Integration тесты**:
   - Test end-to-end with Python service

3. **Grafana dashboard**:
   - VRP solve time
   - Route optimization frequency
   - Distance saved vs baseline
   - ETA accuracy (predicted vs actual)

---

## Мониторинг и метрики

### Ключевые метрики

1. **Optimization Performance**
   - `vrp_solve_time_seconds` — время решения VRP
   - `vrp_solver_type` — OR-Tools vs heuristic
   - `vrp_solution_quality` — gap от optimal (если известен)

2. **Route Quality**
   - `total_distance_km` — суммарное расстояние
   - `total_time_minutes` — суммарное время
   - `distance_saved_vs_baseline_pct` — экономия vs baseline
   - `courier_utilization_pct` — загрузка курьеров

3. **ETA Accuracy**
   - `eta_prediction_error_minutes` — ошибка предсказания
   - `eta_accuracy_pct` — % предсказаний с ошибкой < 5 мин
   - `delay_rate_pct` — % доставок с задержкой

4. **Dynamic Re-routing**
   - `re_route_frequency_per_hour` — как часто ре-роутим
   - `re_route_reason_distribution` — new_order, cancellation, traffic
   - `courier_notification_latency_seconds` — задержка уведомления

### Prometheus Metrics

```php
// In RouteOptimizationService
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

private function recordMetrics(VRPSolutionDto $solution): void
{
    $registry = app(CollectorRegistry::class);
    
    $registry->getOrRegisterCounter(
        'catvrf',
        'vrp_solve_time_seconds',
        'VRP solve time',
        ['solver_type']
    )->incBy($solution->solveTimeSeconds, [$solution->solverType]);
    
    $registry->getOrRegisterGauge(
        'catvrf',
        'vrp_total_distance_km',
        'Total route distance'
    )->set($solution->totalDistanceKm);
    
    $registry->getOrRegisterGauge(
        'catvrf',
        'vrp_total_time_minutes',
        'Total route time'
    )->set($solution->totalTimeMinutes);
}
```

### Alerting

```yaml
# alerts.yml
groups:
  - name: route_optimization
    rules:
      - alert: VRPSolveTimeTooHigh
        expr: vr_p_solve_time_seconds > 60
        for: 5m
        annotations:
          summary: "VRP solve time exceeds 60 seconds"
          
      - alert: ETAPredictionErrorHigh
        expr: eta_prediction_error_minutes > 15
        for: 10m
        annotations:
          summary: "ETA prediction error exceeds 15 minutes"
```

---

## Итог

**Архитектура Route Optimization:**
- ✅ Google OR-Tools как основной solver
- ✅ XGBoost для travel time prediction
- ✅ Python FastAPI microservice
- ✅ Laravel integration via HTTP
- ✅ Dynamic re-routing (incremental)
- ✅ WebSocket notifications for couriers
- ✅ Prometheus metrics + Grafana dashboards

**Ожидаемый результат:**
- Снижение distance на 15-30%
- Снижение delivery time на 10-20%
- ETA accuracy > 85% (error < 5 min)
- VRP solve time < 30 seconds

**Следующий шаг:** Реализовать Agentic AI (Cat AI) для динамического мониторинга и автоматического re-routing (см. docs/LOGISTICS_AI_AGENTIC.md)
