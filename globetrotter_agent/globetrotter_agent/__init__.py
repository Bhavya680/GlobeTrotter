"""Package initialization and agent exposure."""
from .agent import globetrotter_assistant as root_agent
from .sub_agents.trip_planner.agent import trip_planner_agent
from .sub_agents.destination_expert.agent import destination_expert_agent
from .sub_agents.budget_advisor.agent import budget_advisor_agent
from .sub_agents.itinerary_builder.agent import itinerary_builder_agent
from .sub_agents.community_guide.agent import community_guide_agent
from .sub_agents.admin_analyst.agent import admin_analyst_agent

# Attach sub-agents to the root orchestrator
root_agent.sub_agents = [
    trip_planner_agent,
    destination_expert_agent,
    budget_advisor_agent,
    itinerary_builder_agent,
    community_guide_agent,
    admin_analyst_agent
]
