"""
Agentic AI API endpoints
"""

from fastapi import APIRouter, HTTPException
from typing import Optional
import logging

from ..agent import LogisticsAgent
from ..services.feature_store import FeatureStoreService
from ..services.ml_inference import MLInferenceService
from ..core.logging import get_logger

logger = get_logger(__name__)
router = APIRouter(prefix="/v1/agent", tags=["agent"])

# Global agent instance (would be initialized at startup)
_agent: Optional[LogisticsAgent] = None


def get_agent(
    feature_store: FeatureStoreService,
    ml_service: MLInferenceService
) -> LogisticsAgent:
    """Get or create agent instance"""
    global _agent
    if _agent is None:
        _agent = LogisticsAgent(
            feature_store=feature_store,
            ml_service=ml_service,
            enable_autonomous=False  # Start in supervised mode
        )
    return _agent


@router.post("/run-cycle")
async def run_agent_cycle(
    tenant_id: int,
    zone_id: Optional[str] = None,
    autonomous: bool = False,
    feature_store: FeatureStoreService = None,
    ml_service: MLInferenceService = None
):
    """
    Run a full agent cycle: observe -> decide -> act
    
    - autonomous=false: Supervised mode (actions require approval)
    - autonomous=true: Autonomous mode (actions executed automatically)
    """
    agent = get_agent(feature_store, ml_service)
    
    try:
        result = agent.run_cycle(
            tenant_id=tenant_id,
            zone_id=zone_id,
            require_approval=not autonomous
        )
        return result
    except Exception as e:
        logger.error(f"Agent cycle failed: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))


@router.get("/status")
async def get_agent_status(
    feature_store: FeatureStoreService = None,
    ml_service: MLInferenceService = None
):
    """Get current agent status and statistics"""
    agent = get_agent(feature_store, ml_service)
    return agent.get_status()


@router.post("/observe")
async def agent_observe(
    tenant_id: int,
    zone_id: Optional[str] = None,
    feature_store: FeatureStoreService = None,
    ml_service: MLInferenceService = None
):
    """
    Observe the current state of the logistics system
    Returns observations (anomalies, issues, opportunities)
    """
    agent = get_agent(feature_store, ml_service)
    
    try:
        observations = agent.observe(tenant_id, zone_id)
        return {
            "tenant_id": tenant_id,
            "zone_id": zone_id,
            "observations_count": len(observations),
            "observations": [
                {
                    "type": obs.observation_type,
                    "description": obs.description,
                    "data": obs.data,
                    "timestamp": obs.timestamp
                }
                for obs in observations
            ]
        }
    except Exception as e:
        logger.error(f"Agent observation failed: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/configure")
async def configure_agent(
    autonomous: bool = False,
    feature_store: FeatureStoreService = None,
    ml_service: MLInferenceService = None
):
    """
    Configure agent mode
    
    - autonomous=false: Supervised mode (requires human approval)
    - autonomous=true: Autonomous mode (executes actions automatically)
    """
    global _agent
    
    if _agent is None:
        _agent = LogisticsAgent(
            feature_store=feature_store,
            ml_service=ml_service,
            enable_autonomous=autonomous
        )
    else:
        _agent.enable_autonomous = autonomous
    
    logger.info(f"Agent configured: autonomous={autonomous}")
    
    return {
        "status": "configured",
        "autonomous_mode": autonomous,
        "message": "Agent mode updated successfully"
    }
