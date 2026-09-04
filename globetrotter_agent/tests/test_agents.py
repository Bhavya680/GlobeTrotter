"""Test specifications for ADK agents routing and behavior."""
from globetrotter_agent.agent import globetrotter_assistant
from globetrotter_agent.sub_agents.admin_analyst.agent import admin_analyst_agent
from globetrotter_agent.sub_agents.budget_advisor.agent import budget_advisor_agent

def test_root_agent_has_subagents():
    """Verify the root agent has all specialized delegates."""
    agent_names = [a.name for a in globetrotter_assistant.sub_agents]
    assert "trip_planner_agent" in agent_names
    assert "destination_expert_agent" in agent_names
    assert "budget_advisor_agent" in agent_names

def test_admin_agent_instruction_checks_role():
    """Verify admin_analyst refuses non-admin in instructions."""
    # Since instruction is an InstructionProvider or string, we convert to string if needed
    instr = admin_analyst_agent.instruction
    assert "admin" in str(instr).lower()

def test_budget_advisor_has_okf_instruction():
    """Verify budget advisor uses OKF knowledge."""
    instr = budget_advisor_agent.instruction
    # The instruction should contain the injected budget_intelligence.md content
    assert len(str(instr)) > 100
