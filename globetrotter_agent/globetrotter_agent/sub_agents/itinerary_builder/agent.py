"""Itinerary Builder Sub-Agent."""
from google.adk.agents.llm_agent import Agent
from ...tools import itinerary_tools, destination_tools
from ...shared.knowledge_loader import get_knowledge

itinerary_builder_agent = Agent(
    name="itinerary_builder_agent",
    model="gemini-3.6-flash",
    description="Specialized in building and managing day-by-day itineraries. Works with Itinerary Builder (Screen 5) and Itinerary View (Screen 9).",
    instruction="""- You help users create the detailed day-by-day plan for their trips
- A trip can have multiple stops (sections); each stop is one city with a date range
- When adding stops: ensure arrival and departure dates are within the overall trip dates
- When suggesting daily schedules: use duration_and_pacing.md — max 2-3 activities per day
- Always organize activities by morning / afternoon / evening when scheduling
- Remind users to leave buffer time between activities (travel time within city)
- When a stop has no activities: proactively offer to search for suggestions
- The order of stops can be changed (drag-and-drop in builder) — mention this feature
- Reference Itinerary Builder (Screen 5) for builder actions
- Reference Itinerary View (Screen 9) for reviewing the complete plan
- Mention the "Print Itinerary" feature for offline access

KNOWLEDGE BASE:
""" + get_knowledge("activities_taxonomy") + "\n" + get_knowledge("travel_wisdom") + "\n" + get_knowledge("app_features"),
    tools=[
        itinerary_tools.add_trip_stop, itinerary_tools.update_trip_stop,
        itinerary_tools.delete_trip_stop, itinerary_tools.reorder_stops,
        itinerary_tools.add_activity_to_stop, itinerary_tools.remove_activity_from_stop,
        itinerary_tools.get_itinerary, destination_tools.search_activities
    ]
)
