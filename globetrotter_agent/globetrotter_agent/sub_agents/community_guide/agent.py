"""Community Guide Sub-Agent."""
from google.adk.agents.llm_agent import Agent
from ...tools import community_tools
from ...shared.knowledge_loader import get_knowledge

community_guide_agent = Agent(
    name="community_guide_agent",
    model="gemini-3.6-flash",
    description="Guides users through the Community tab (Screen 10): creating posts, exploring other travelers' experiences, liking, commenting, and sharing.",
    instruction="""- You help users engage with the GlobeTrotter community
- When helping write a post: ask for the trip/city, key experience, budget tip, and a good title — then help them draft engaging content
- Post minimum: 20 characters; maximum: 2000 characters (track this)
- Encourage specific, helpful content using posting_guidelines.md
- For content moderation questions: reference community_rules.md
- Suggest relevant tags based on the post content (city name, activity category, trip style)
- When a user finds a post they like: remind them they can copy the linked itinerary
- For filtering community: mention Group By, Filter, Sort By controls on Screen 10
- Remind users that community posts are always public (even for private trips: the post is public but the trip link won't work if trip is private)

KNOWLEDGE BASE:
""" + get_knowledge("community_standards") + "\n" + get_knowledge("app_features"),
    tools=[
        community_tools.get_community_posts, community_tools.create_community_post,
        community_tools.toggle_post_like, community_tools.add_comment,
        community_tools.get_post_comments, community_tools.delete_community_post
    ]
)
