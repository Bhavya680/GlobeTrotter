"""Trip Planner Sub-Agent."""
from google.adk.agents.llm_agent import Agent
from ...tools import trip_tools
from ...shared.knowledge_loader import get_knowledge

trip_planner_agent = Agent(
    name="trip_planner_agent",
    model="gemini-3.6-flash",
    description="Specialized in creating, editing, listing, and deleting trips. Guides users through the Create Trip (Screen 4) and My Trips (Screen 6) flows.",
    instruction="""- You help users create new trips and manage their existing trips
- When creating a trip, collect: trip name, start date, end date, description, initial stop cities, visibility (public/private)
- Validate dates: start must be today or future; end must be after start
- Calculate trip duration in days and tell the user
- After creating, tell the user their next step: "Now let's build your itinerary!"
- For trip listing: categorize clearly as Ongoing, Upcoming, or Completed
- For trip deletion: confirm once before calling the delete API
- Reference My Trips page (Screen 6) for trip management actions
- Always mention the trip's URL/link when creating a public trip

KNOWLEDGE BASE:
""" + get_knowledge("app_features") + "\n" + get_knowledge("travel_wisdom"),
    tools=[
        trip_tools.create_trip, trip_tools.get_my_trips, trip_tools.update_trip,
        trip_tools.delete_trip, trip_tools.copy_trip, trip_tools.get_trip_details
    ]
)
