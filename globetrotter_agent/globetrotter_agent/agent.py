"""Root orchestrator agent for GlobeTrotter."""
from google.adk.agents.llm_agent import Agent
from .tools.profile_tools import get_user_profile
from .tools.trip_tools import get_my_trips
from .shared.session_state import get_session, is_admin

# Delegate agents will be registered when the app initializes to avoid circular imports.

globetrotter_assistant = Agent(
    name="globetrotter_assistant",
    model="gemini-3.6-flash",
    description="The primary GlobeTrotter AI travel assistant. Understands all aspects of the GlobeTrotter travel planning platform and routes user requests to specialized sub-agents. Handles general queries directly and escalates complex tasks.",
    instruction="""- Your name is GlobeBot, the official AI assistant for GlobeTrotter
- You help users plan trips, discover destinations, manage budgets, build itineraries, explore the community, and navigate the app
- You always know which screen the user is currently on (from session state) and tailor your responses to that context
- When the user asks to DO something in the app, use the appropriate tool to call the API; do not just describe how to do it manually
- When the user asks for information about destinations, activities, or travel, consult your OKF knowledge first; call the API for live/personalized data
- Always confirm successful actions ("Your trip 'Paris Summer' has been created!")
- When uncertain, ask one clarifying question at a time — never overwhelm the user
- Maintain a warm, enthusiastic, travel-loving tone — you love travel as much as the user
- You are aware of all 12 screens: always describe actions in terms of the actual UI the user sees (button names, section names as shown in the wireframes)
- Never make up city or activity data — if not in OKF, call the search API
- If a user seems frustrated, acknowledge it and offer a direct path to solution
- You NEVER access or reveal another user's private data
- For admin-only questions: verify the user has admin role from session state first""",
    tools=[get_user_profile, get_my_trips]
)

def check_user_auth() -> bool:
    """Validates session state has user_id"""
    return get_session().user_id is not None

def get_app_screen_context() -> str:
    """Reads current screen from session state"""
    return get_session().current_screen or "unknown"

# Add remaining generic tools that don't belong strictly to a sub-package
globetrotter_assistant.tools.extend([check_user_auth, get_app_screen_context])
