from typing import List, Tuple
import math

def haversine(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    """Calculate distance between two points in km using Haversine formula."""
    R = 6371  # Earth radius in km
    
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) *
         math.sin(dlon / 2) ** 2)
    
    c = 2 * math.asin(math.sqrt(a))
    return R * c

def compute_distance_matrix(locations: List[Tuple[float, float]]) -> List[List[float]]:
    """Compute distance matrix for all locations."""
    n = len(locations)
    matrix = [[0.0] * n for _ in range(n)]
    
    for i in range(n):
        for j in range(n):
            if i != j:
                matrix[i][j] = haversine(
                    locations[i][0], locations[i][1],
                    locations[j][0], locations[j][1]
                )
    
    return matrix

def solve_vrp_nearest_neighbor(
    depot: Tuple[float, float],
    orders: List[Tuple[float, float]],
    demands: List[float],
    vehicle_capacities: List[float],
    time_limit: int = 30
) -> dict:
    """
    Solve VRP using Nearest Neighbor heuristic (fast fallback).
    
    Returns:
        dict with routes, total_distance_km, total_time_minutes, solver_type
    """
    locations = [depot] + orders
    distance_matrix = compute_distance_matrix(locations)
    
    n_orders = len(orders)
    n_vehicles = len(vehicle_capacities)
    
    # Initialize routes
    routes = [[] for _ in range(n_vehicles)]
    vehicle_loads = [0.0] * n_vehicles
    unassigned = set(range(1, n_orders + 1))  # Order indices (1-based, 0 is depot)
    
    # Assign orders to vehicles
    for vehicle_idx in range(n_vehicles):
        if not unassigned:
            break
        
        current_location = 0  # Start at depot
        vehicle_capacity = vehicle_capacities[vehicle_idx]
        
        while unassigned and vehicle_loads[vehicle_idx] < vehicle_capacity:
            # Find nearest unassigned order
            nearest = None
            nearest_dist = float('inf')
            
            for order_idx in unassigned:
                if vehicle_loads[vehicle_idx] + demands[order_idx - 1] <= vehicle_capacity:
                    dist = distance_matrix[current_location][order_idx]
                    if dist < nearest_dist:
                        nearest = order_idx
                        nearest_dist = dist
            
            if nearest is None:
                break  # No more orders fit in this vehicle
            
            # Assign order
            routes[vehicle_idx].append(nearest - 1)  # Convert to 0-based
            vehicle_loads[vehicle_idx] += demands[nearest - 1]
            current_location = nearest
            unassigned.remove(nearest)
    
    # Calculate total distance
    total_distance = 0.0
    for route in routes:
        if not route:
            continue
        
        # From depot to first order
        total_distance += distance_matrix[0][route[0] + 1]
        
        # Between orders
        for i in range(len(route) - 1):
            total_distance += distance_matrix[route[i] + 1][route[i + 1] + 1]
        
        # From last order back to depot
        total_distance += distance_matrix[route[-1] + 1][0]
    
    # Estimate time (assuming 30 km/h average speed)
    total_time = (total_distance / 30.0) * 60  # Convert to minutes
    
    return {
        "routes": routes,
        "total_distance_km": round(total_distance, 2),
        "total_time_minutes": round(total_time, 2),
        "solver_type": "nearest_neighbor"
    }
