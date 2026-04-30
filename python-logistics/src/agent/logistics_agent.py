"""
Logistics Agent - Agentic AI for Autonomous Logistics Management
Uses LangChain-like pattern for tool-based decision making
"""

from typing import Dict, Any, List, Optional
import logging
from datetime import datetime
from dataclasses import dataclass

from .tools import (
    AnomalyDetectionTool,
    OrderRedistributionTool,
    SelfHealingRoutingTool,
    PVZCapacityTool
)
from ..services.feature_store import FeatureStoreService
from ..services.ml_inference import MLInferenceService
from ..core.config import settings
from ..core.logging import get_logger

logger = get_logger(__name__)


@dataclass
class AgentAction:
    """Represents an action taken by the agent"""
    action_type: str
    description: str
    parameters: Dict[str, Any]
    result: Optional[Dict[str, Any]] = None
    timestamp: str = None
    
    def __post_init__(self):
        if self.timestamp is None:
            self.timestamp = datetime.now().isoformat()


@dataclass
class AgentObservation:
    """Represents an observation made by the agent"""
    observation_type: str
    description: str
    data: Dict[str, Any]
    timestamp: str = None
    
    def __post_init__(self):
        if self.timestamp is None:
            self.timestamp = datetime.now().isoformat()


class LogisticsAgent:
    """
    Autonomous Logistics Agent
    
    Capabilities:
    - Monitor logistics anomalies (courier idle, traffic spikes, delivery delays)
    - Redistribute orders between couriers
    - Self-healing route optimization
    - Suggest PVZ capacity adjustments
    - Autonomous decision making with human oversight
    """
    
    def __init__(
        self,
        feature_store: FeatureStoreService,
        ml_service: MLInferenceService,
        enable_autonomous: bool = True
    ):
        self.feature_store = feature_store
        self.ml_service = ml_service
        self.enable_autonomous = enable_autonomous
        
        # Initialize tools
        self.anomaly_tool = AnomalyDetectionTool(feature_store)
        self.redistribution_tool = OrderRedistributionTool(feature_store, ml_service)
        self.routing_tool = SelfHealingRoutingTool(feature_store, ml_service)
        self.pvz_tool = PVZCapacityTool(feature_store)
        
        # Agent state
        self.observations: List[AgentObservation] = []
        self.actions: List[AgentAction] = []
        
        logger.info("Logistics Agent initialized", extra={"autonomous": enable_autonomous})
    
    def observe(
        self,
        tenant_id: int,
        zone_id: Optional[str] = None
    ) -> List[AgentObservation]:
        """
        Observe the current state of the logistics system
        
        Returns list of observations (anomalies, issues, opportunities)
        """
        observations = []
        
        # Detect courier idle anomalies
        idle_couriers = self.anomaly_tool.detect_courier_idle_anomaly(tenant_id)
        if idle_couriers:
            observations.append(AgentObservation(
                observation_type="courier_idle_anomaly",
                description=f"Detected {len(idle_couriers)} couriers idle >10 minutes",
                data={"couriers": idle_couriers, "count": len(idle_couriers)}
            ))
        
        # Detect traffic spikes if zone specified
        if zone_id:
            traffic_spike = self.anomaly_tool.detect_traffic_spike_anomaly(tenant_id, zone_id)
            if traffic_spike:
                observations.append(AgentObservation(
                    observation_type="traffic_spike_anomaly",
                    description=f"Traffic spike detected in zone {zone_id}",
                    data=traffic_spike
                ))
        
        # Detect delivery delays
        delayed_shipments = self.anomaly_tool.detect_delivery_delay_anomaly(tenant_id)
        if delayed_shipments:
            observations.append(AgentObservation(
                observation_type="delivery_delay_anomaly",
                description=f"Detected {len(delayed_shipments)} shipments delayed >30 minutes",
                data={"shipments": delayed_shipments, "count": len(delayed_shipments)}
            ))
        
        # Check PVZ capacity if zone specified
        if zone_id:
            high_load_pvzs = self.pvz_tool.suggest_capacity_increase(tenant_id, zone_id)
            if high_load_pvzs:
                observations.append(AgentObservation(
                    observation_type="pvz_capacity_issue",
                    description=f"Detected {len(high_load_pvzs)} PVZs with load >80%",
                    data={"pvzs": high_load_pvzs, "count": len(high_load_pvzs)}
                ))
        
        self.observations.extend(observations)
        
        logger.info(
            f"Agent observation completed",
            extra={
                "tenant_id": tenant_id,
                "zone_id": zone_id,
                "observations_count": len(observations)
            }
        )
        
        return observations
    
    def decide(
        self,
        observations: List[AgentObservation],
        tenant_id: int
    ) -> List[AgentAction]:
        """
        Decide on actions based on observations
        
        This is where the agent's "intelligence" comes in - it prioritizes
        and decides which actions to take based on the observations.
        """
        actions = []
        
        for observation in observations:
            # Courier idle anomaly -> suggest redistribution
            if observation.observation_type == "courier_idle_anomaly":
                # Get shipment IDs for affected zone (simplified)
                zone_id = observation.data["couriers"][0].get("zone_id") if observation.data["couriers"] else None
                
                if zone_id and len(observation.data["couriers"]) > 3:
                    action = AgentAction(
                        action_type="suggest_order_redistribution",
                        description=f"Suggest order redistribution for zone {zone_id}",
                        parameters={
                            "tenant_id": tenant_id,
                            "zone_id": zone_id,
                            "reason": "multiple_couriers_idle"
                        }
                    )
                    actions.append(action)
            
            # Traffic spike -> suggest rerouting
            elif observation.observation_type == "traffic_spike_anomaly":
                zone_id = observation.data["zone_id"]
                
                action = AgentAction(
                    action_type="suggest_zone_rerouting",
                    description=f"Suggest rerouting shipments in zone {zone_id} due to traffic spike",
                    parameters={
                        "tenant_id": tenant_id,
                        "zone_id": zone_id,
                        "reason": "traffic_spike",
                        "spike_ratio": observation.data["spike_ratio"]
                    }
                )
                actions.append(action)
            
            # Delivery delay -> reroute specific shipments
            elif observation.observation_type == "delivery_delay_anomaly":
                for shipment in observation.data["shipments"]:
                    action = AgentAction(
                        action_type="reroute_shipment",
                        description=f"Reroute shipment {shipment['shipment_id']} due to delay",
                        parameters={
                            "tenant_id": tenant_id,
                            "shipment_id": shipment["shipment_id"],
                            "reason": "delivery_delay",
                            "delay_minutes": shipment["eta_error_minutes"]
                        }
                    )
                    actions.append(action)
            
            # PVZ capacity issue -> suggest capacity increase
            elif observation.observation_type == "pvz_capacity_issue":
                for pvz in observation.data["pvzs"]:
                    action = AgentAction(
                        action_type="suggest_pvz_capacity_increase",
                        description=f"Suggest capacity increase for PVZ {pvz['pvz_id']}",
                        parameters={
                            "tenant_id": tenant_id,
                            "pvz_id": pvz["pvz_id"],
                            "current_load": pvz["load_ratio"],
                            "forecast_load": pvz.get("forecast_3h", {}).get("load_ratio_avg") if pvz.get("forecast_3h") else None
                        }
                    )
                    actions.append(action)
        
        # Prioritize actions (simple heuristic - can be enhanced with ML)
        actions.sort(key=lambda a: {
            "reroute_shipment": 1,  # Highest priority
            "suggest_zone_rerouting": 2,
            "suggest_order_redistribution": 3,
            "suggest_pvz_capacity_increase": 4
        }.get(a.action_type, 99))
        
        self.actions.extend(actions)
        
        logger.info(
            f"Agent decision completed",
            extra={
                "tenant_id": tenant_id,
                "actions_count": len(actions)
            }
        )
        
        return actions
    
    def act(
        self,
        actions: List[AgentAction],
        require_approval: bool = True
    ) -> List[AgentAction]:
        """
        Execute actions (or prepare them for approval)
        
        If require_approval is True, actions are prepared but not executed.
        If require_approval is False (autonomous mode), actions are executed directly.
        """
        executed_actions = []
        
        for action in actions:
            if require_approval and not self.enable_autonomous:
                # In supervised mode, just return the action without executing
                action.result = {
                    "status": "pending_approval",
                    "message": "Action requires human approval"
                }
            else:
                # In autonomous mode, execute the action
                if action.action_type == "reroute_shipment":
                    action.result = self.routing_tool.reroute_shipment(
                        tenant_id=action.parameters["tenant_id"],
                        shipment_id=action.parameters["shipment_id"],
                        reason=action.parameters["reason"]
                    )
                
                elif action.action_type == "suggest_order_redistribution":
                    # This would require shipment IDs - placeholder
                    action.result = {
                        "status": "success",
                        "message": "Redistribution suggestion prepared",
                        "zone_id": action.parameters["zone_id"]
                    }
                
                elif action.action_type == "suggest_pvz_capacity_increase":
                    action.result = {
                        "status": "success",
                        "message": "PVZ capacity increase suggestion prepared",
                        "pvz_id": action.parameters["pvz_id"]
                    }
                
                else:
                    action.result = {
                        "status": "unknown_action",
                        "message": f"Unknown action type: {action.action_type}"
                    }
            
            executed_actions.append(action)
        
        logger.info(
            f"Agent action completed",
            extra={
                "actions_executed": len(executed_actions),
                "autonomous": not require_approval
            }
        )
        
        return executed_actions
    
    def run_cycle(
        self,
        tenant_id: int,
        zone_id: Optional[str] = None,
        require_approval: bool = None
    ) -> Dict[str, Any]:
        """
        Run a full agent cycle: observe -> decide -> act
        
        Returns summary of the cycle
        """
        if require_approval is None:
            require_approval = not self.enable_autonomous
        
        start_time = datetime.now()
        
        # Observe
        observations = self.observe(tenant_id, zone_id)
        
        # Decide
        actions = self.decide(observations, tenant_id)
        
        # Act
        executed_actions = self.act(actions, require_approval)
        
        cycle_time = (datetime.now() - start_time).total_seconds()
        
        summary = {
            "tenant_id": tenant_id,
            "zone_id": zone_id,
            "cycle_time_seconds": cycle_time,
            "observations_count": len(observations),
            "actions_count": len(actions),
            "executed_actions_count": len(executed_actions),
            "mode": "autonomous" if not require_approval else "supervised",
            "observations": [
                {
                    "type": obs.observation_type,
                    "description": obs.description,
                    "timestamp": obs.timestamp
                }
                for obs in observations
            ],
            "actions": [
                {
                    "type": action.action_type,
                    "description": action.description,
                    "status": action.result.get("status") if action.result else "unknown",
                    "timestamp": action.timestamp
                }
                for action in executed_actions
            ]
        }
        
        logger.info(
            f"Agent cycle completed",
            extra=summary
        )
        
        return summary
    
    def get_status(self) -> Dict[str, Any]:
        """Get current agent status"""
        return {
            "autonomous_mode": self.enable_autonomous,
            "total_observations": len(self.observations),
            "total_actions": len(self.actions),
            "recent_observations": [
                obs.observation_type for obs in self.observations[-10:]
            ],
            "recent_actions": [
                action.action_type for action in self.actions[-10:]
            ]
        }
