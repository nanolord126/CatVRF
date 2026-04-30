"""
LLM-Enhanced Logistics Agent
Uses LangChain with OpenAI/Grok for intelligent decision making
"""

from typing import Dict, Any, List, Optional
import logging
from datetime import datetime
from dataclasses import dataclass

try:
    from langchain.agents import AgentExecutor, create_openai_tools_agent
    from langchain_openai import ChatOpenAI
    from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
    from langchain_core.tools import BaseTool
    LANGCHAIN_AVAILABLE = True
except ImportError:
    LANGCHAIN_AVAILABLE = False
    logging.warning("LangChain not installed, using rule-based agent")

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
from .logistics_agent import LogisticsAgent, AgentAction, AgentObservation

logger = get_logger(__name__)


class ClickHouseQueryTool(BaseTool):
    """LangChain tool for querying ClickHouse"""
    name: str = "clickhouse_query"
    description: str = "Query ClickHouse feature store for logistics data"
    
    def __init__(self, feature_store: FeatureStoreService):
        super().__init__()
        self.feature_store = feature_store
    
    def _run(self, query: str) -> str:
        """Execute ClickHouse query"""
        try:
            result = self.feature_store.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            output = []
            for row in rows:
                output.append(dict(zip(columns, row)))
            
            return str(output)[:1000]  # Limit output length
        except Exception as e:
            return f"Error: {str(e)}"


class CourierAssignmentTool(BaseTool):
    """LangChain tool for courier assignment"""
    name: str = "courier_assignment"
    description: str = "Assign best courier to shipment using ML model"
    
    def __init__(self, ml_service: MLInferenceService, feature_store: FeatureStoreService):
        super().__init__()
        self.ml_service = ml_service
        self.feature_store = feature_store
    
    def _run(self, shipment_id: str, zone_id: str) -> str:
        """Assign courier to shipment"""
        try:
            # This would get actual data from feature store
            assignment_model = self.ml_service.get_courier_assignment_model()
            # Placeholder - would use real data
            return f"Assigned courier for shipment {shipment_id} in zone {zone_id}"
        except Exception as e:
            return f"Error: {str(e)}"


class RouteOptimizationTool(BaseTool):
    """LangChain tool for route optimization"""
    name: str = "route_optimization"
    description: str = "Optimize route for shipment using ML predictions"
    
    def __init__(self, ml_service: MLInferenceService, feature_store: FeatureStoreService):
        super().__init__()
        self.ml_service = ml_service
        self.feature_store = feature_store
    
    def _run(self, shipment_id: str) -> str:
        """Optimize route for shipment"""
        try:
            eta_model = self.ml_service.get_eta_prediction_model()
            # Placeholder - would use real data
            return f"Optimized route for shipment {shipment_id}"
        except Exception as e:
            return f"Error: {str(e)}"


class LLMLogisticsAgent:
    """
    LLM-Enhanced Logistics Agent using LangChain
    
    Uses OpenAI/Grok API for intelligent decision making with tool access
    """
    
    def __init__(
        self,
        feature_store: FeatureStoreService,
        ml_service: MLInferenceService,
        enable_autonomous: bool = True,
        llm_provider: str = "openai",
        llm_model: str = "gpt-4-turbo"
    ):
        self.feature_store = feature_store
        self.ml_service = ml_service
        self.enable_autonomous = enable_autonomous
        self.llm_provider = llm_provider
        self.llm_model = llm_model
        
        # Initialize rule-based tools
        self.anomaly_tool = AnomalyDetectionTool(feature_store)
        self.redistribution_tool = OrderRedistributionTool(feature_store, ml_service)
        self.routing_tool = SelfHealingRoutingTool(feature_store, ml_service)
        self.pvz_tool = PVZCapacityTool(feature_store)
        
        # Initialize LLM agent if available
        self.llm_agent = None
        self.rule_based_agent = LogisticsAgent(feature_store, ml_service, enable_autonomous)
        
        if LANGCHAIN_AVAILABLE and settings.agent_llm_api_key:
            self._init_llm_agent()
        else:
            logger.warning("LLM agent not available, using rule-based agent")
        
        logger.info(
            "LLM Logistics Agent initialized",
            extra={
                "llm_provider": llm_provider,
                "llm_model": llm_model,
                "autonomous": enable_autonomous,
                "llm_available": self.llm_agent is not None
            }
        )
    
    def _init_llm_agent(self):
        """Initialize LangChain agent with tools"""
        try:
            # Initialize LLM
            if self.llm_provider == "openai":
                llm = ChatOpenAI(
                    model=self.llm_model,
                    api_key=settings.agent_llm_api_key,
                    temperature=0.1  # Low temperature for consistent decisions
                )
            else:
                logger.warning(f"LLM provider {self.llm_provider} not implemented")
                return
            
            # Create tools
            tools = [
                ClickHouseQueryTool(self.feature_store),
                CourierAssignmentTool(self.ml_service, self.feature_store),
                RouteOptimizationTool(self.ml_service, self.feature_store)
            ]
            
            # Create prompt
            prompt = ChatPromptTemplate.from_messages([
                ("system", """You are an intelligent logistics agent for CatVRF platform.
Your role is to monitor logistics operations and make autonomous decisions to optimize performance.

You have access to tools for:
- Querying ClickHouse feature store for real-time data
- Assigning couriers to shipments using ML models
- Optimizing routes based on traffic and conditions

Always consider:
1. Customer satisfaction (minimize delays)
2. Operational efficiency (reduce empty miles)
3. Cost optimization
4. Courier/taxi driver satisfaction

When you detect anomalies:
- Courier idle >10 minutes: suggest redistribution
- Traffic spike >2x: suggest rerouting
- Delivery delay >30 min: reroute immediately
- PVZ load >80%: suggest capacity increase

Be concise and action-oriented in your responses."""),
                MessagesPlaceholder(variable_name="agent_scratchpad"),
                ("human", "{input}")
            ])
            
            # Create agent
            agent = create_openai_tools_agent(llm, tools, prompt)
            self.llm_agent = AgentExecutor(
                agent=agent,
                tools=tools,
                verbose=True,
                handle_parsing_errors=True,
                max_iterations=5
            )
            
            logger.info("LangChain LLM agent initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize LLM agent: {e}", exc_info=True)
            self.llm_agent = None
    
    def observe(
        self,
        tenant_id: int,
        zone_id: Optional[str] = None
    ) -> List[AgentObservation]:
        """
        Observe the current state of the logistics system
        Uses rule-based detection for consistency
        """
        return self.rule_based_agent.observe(tenant_id, zone_id)
    
    def decide_with_llm(
        self,
        observations: List[AgentObservation],
        tenant_id: int,
        context: Optional[Dict[str, Any]] = None
    ) -> List[AgentAction]:
        """
        Use LLM to make intelligent decisions based on observations
        """
        if not self.llm_agent:
            # Fallback to rule-based decision
            return self.rule_based_agent.decide(observations, tenant_id)
        
        try:
            # Format observations for LLM
            obs_summary = "\n".join([
                f"- {obs.observation_type}: {obs.description}"
                for obs in observations
            ])
            
            context_str = f"\nContext: {context}" if context else ""
            
            prompt = f"""
Tenant ID: {tenant_id}
Current observations:
{obs_summary}
{context_str}

What actions should I take? Be specific and actionable.
"""
            
            # Run LLM agent
            result = self.llm_agent.invoke({"input": prompt})
            
            # Parse LLM response into actions (simplified)
            # In production, would use structured output parsing
            actions = []
            for obs in observations:
                if obs.observation_type == "delivery_delay_anomaly":
                    for shipment in obs.data.get("shipments", []):
                        actions.append(AgentAction(
                            action_type="reroute_shipment",
                            description=f"LLM decided to reroute shipment {shipment['shipment_id']}",
                            parameters={
                                "tenant_id": tenant_id,
                                "shipment_id": shipment["shipment_id"],
                                "reason": "llm_decision"
                            }
                        ))
            
            logger.info(
                f"LLM decision completed",
                extra={
                    "tenant_id": tenant_id,
                    "actions_count": len(actions),
                    "llm_response": result.get("output", "")[:500]
                }
            )
            
            return actions
            
        except Exception as e:
            logger.error(f"LLM decision failed, falling back to rule-based: {e}")
            return self.rule_based_agent.decide(observations, tenant_id)
    
    def run_cycle(
        self,
        tenant_id: int,
        zone_id: Optional[str] = None,
        require_approval: bool = None,
        use_llm: bool = True
    ) -> Dict[str, Any]:
        """
        Run full agent cycle with LLM decision making
        
        Args:
            tenant_id: Tenant ID
            zone_id: Optional zone ID
            require_approval: Whether to require human approval
            use_llm: Whether to use LLM for decision making
        """
        if require_approval is None:
            require_approval = not self.enable_autonomous
        
        start_time = datetime.now()
        
        # Observe (always rule-based for consistency)
        observations = self.observe(tenant_id, zone_id)
        
        # Decide (with LLM if available and enabled)
        if use_llm and self.llm_agent:
            actions = self.decide_with_llm(observations, tenant_id)
        else:
            actions = self.rule_based_agent.decide(observations, tenant_id)
        
        # Act
        executed_actions = self.rule_based_agent.act(actions, require_approval)
        
        cycle_time = (datetime.now() - start_time).total_seconds()
        
        summary = {
            "tenant_id": tenant_id,
            "zone_id": zone_id,
            "cycle_time_seconds": cycle_time,
            "observations_count": len(observations),
            "actions_count": len(actions),
            "executed_actions_count": len(executed_actions),
            "mode": "autonomous_llm" if (use_llm and self.llm_agent and not require_approval) else "supervised_llm" if use_llm else "rule_based",
            "llm_available": self.llm_agent is not None,
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
            f"LLM Agent cycle completed",
            extra=summary
        )
        
        return summary
    
    def get_status(self) -> Dict[str, Any]:
        """Get current agent status"""
        base_status = self.rule_based_agent.get_status()
        base_status["llm_available"] = self.llm_agent is not None
        base_status["llm_provider"] = self.llm_provider if self.llm_agent else None
        base_status["llm_model"] = self.llm_model if self.llm_agent else None
        return base_status
