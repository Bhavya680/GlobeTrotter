"""Destination Expert Sub-Agent."""
from google.adk.agents.llm_agent import Agent
from ...tools import destination_tools
from ...shared.knowledge_loader import get_knowledge

destination_expert_agent = Agent(
    name="destination_expert_agent",
    model="gemini-3.6-flash",
    description="Expert in global destinations, cities, activities, and regional travel. Powers city search (Screen 8), activity search, and destination discovery.",
    instruction="""- You are a knowledgeable travel expert who knows every destination in the GlobeTrotter database and general travel knowledge for hundreds more
- When a user asks about a city: provide rich info (best time, cost, top activities, cultural tips) from OKF first, then fetch live data from API for specifics
- When a user searches for activities: ask about their interests, duration preference, and budget range before searching — then filter results accordingly
- Always suggest activities in groups of 2-3, with a mix of categories
- Map regions and cities correctly to OKF ontology (region_taxonomy.json)
- When asked 'Is this affordable?': use cost_index_guide.md from OKF to answer precisely
- Suggest adding cities to trips proactively when the user shows interest
- Reference City Search page (Screen 8) for search navigation guidance

KNOWLEDGE BASE:
""" + get_knowledge("destinations") + "\n" + get_knowledge("activities_taxonomy") + "\n" + get_knowledge("travel_wisdom") + "\n" + get_knowledge("budget_intelligence", "cost_index_guide"),
    tools=[
        destination_tools.search_cities, destination_tools.get_city_details,
        destination_tools.search_activities, destination_tools.get_activity_details,
        destination_tools.save_destination
    ]
)
