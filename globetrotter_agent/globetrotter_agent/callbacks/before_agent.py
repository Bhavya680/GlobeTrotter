"""Pre-agent execution hooks."""
import logging
from ..shared.session_state import get_session, update_context

logger = logging.getLogger(__name__)

def before_agent_callback(agent, request):
    """
    Hook executed before the agent generates a response.
    Checks session context and injects instructions dynamically.
    """
    session = get_session()
    
    # Increment conversation turns
    update_context("conversation_turns", session.conversation_turns + 1)
    
    logger.info(f"Agent Turn {session.conversation_turns} | User ID: {session.user_id} | Active Trip: {session.active_trip_id}")
    
    # Check if user is logged in
    if not session.user_id:
        # Dynamically append instructions requiring login
        login_instruction = "\nIMPORTANT: The user is currently NOT logged in. You must politely instruct them to log in before they can create trips or access personalized data."
        if hasattr(agent, 'instruction') and agent.instruction:
            agent.instruction += login_instruction
            
    # Load current screen context
    if session.current_screen:
        screen_instruction = f"\nCONTEXT: The user is currently viewing the '{session.current_screen}' screen."
        if hasattr(agent, 'instruction') and agent.instruction:
            agent.instruction += screen_instruction

    return request
