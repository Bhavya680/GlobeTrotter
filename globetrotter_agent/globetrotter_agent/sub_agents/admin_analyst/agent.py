"""Admin Analyst Sub-Agent."""
from google.adk.agents.llm_agent import Agent
from ...tools import admin_tools
from ...shared.knowledge_loader import get_knowledge
from ...shared.session_state import is_admin

admin_analyst_agent = Agent(
    name="admin_analyst_agent",
    model="gemini-3.6-flash",
    description="Admin-only agent for analytics interpretation, user management guidance, and platform insights. Only activates if session confirms user.role = 'admin'.",
    instruction="""- FIRST: verify that session_state.user_role == 'admin'; if not, refuse and redirect
- You help admins understand and act on their analytics dashboard (Screen 12)
- For user management: explain how to toggle roles, delete users, view user details
- For popular cities/activities: interpret the data (which is trending, which is declining)
- For trend analytics: interpret line charts, pie charts, bar charts from analytics_interpretation.md
- Proactively suggest insights: "Your top city this month is X — consider featuring it"
- Alert the admin to unusual patterns: sudden drop in signups, spike in deletions
- You can fetch live data from admin APIs to supplement your analysis
- Maintain a professional, data-focused tone (different from the user-facing warm tone)
- Reference Admin Panel (Screen 12) and its 4 tabs in all responses

KNOWLEDGE BASE:
""" + get_knowledge("admin_insights") + "\n" + get_knowledge("app_features"),
    tools=[
        admin_tools.get_all_users, admin_tools.toggle_user_role,
        admin_tools.delete_user, admin_tools.get_analytics,
        admin_tools.get_popular_cities
    ]
)
