"""Post-agent execution hooks."""
import logging
from ..shared.session_state import get_session, update_context

logger = logging.getLogger(__name__)

def after_agent_callback(agent, response):
    """
    Hook executed after the agent generates a response and tools have completed.
    """
    session = get_session()
    
    # Log the agent's response (truncate to 500 chars)
    if response and hasattr(response, 'text'):
        text = response.text
        truncated = text[:500] + "..." if len(text) > 500 else text
        logger.info(f"Agent Response: {truncated}")
        
    # If the agent has a tool call tracking attribute (depends on specific ADK usage)
    if hasattr(response, 'function_calls') and response.function_calls:
        for call in response.function_calls:
            logger.info(f"Tool executed: {call.name}")
            update_context("tool_call_count", session.tool_call_count + 1)
            
    return response
