from typing import List, Tuple

def predict_time_matrix(
    locations: List[Tuple[float, float]],
    hour: int,
    weather: str
) -> List[List[float]]:
    """
    Predict travel time matrix using simple heuristics.
    In production, this would use XGBoost/LightGBM models.
    """
    from ..utils.distance import compute_distance_matrix
    
    distance_matrix = compute_distance_matrix(locations)
    n = len(locations)
    time_matrix = [[0.0] * n for _ in range(n)]
    
    # Base speed (km/h)
    base_speed = 30.0
    
    # Adjust for hour (rush hours)
    if hour in [8, 9, 18, 19]:
        speed_multiplier = 0.6  # Slower during rush hours
    elif hour in [12, 13]:
        speed_multiplier = 0.8  # Lunch slowdown
    else:
        speed_multiplier = 1.0
    
    # Adjust for weather
    if weather.lower() in ['rain', 'heavy_rain']:
        speed_multiplier *= 0.8
    elif weather.lower() in ['snow', 'blizzard']:
        speed_multiplier *= 0.5
    
    effective_speed = base_speed * speed_multiplier
    
    # Compute time matrix (minutes)
    for i in range(n):
        for j in range(n):
            if i != j:
                distance_km = distance_matrix[i][j]
                time_hours = distance_km / effective_speed
                time_matrix[i][j] = time_hours * 60  # Convert to minutes
    
    return time_matrix
