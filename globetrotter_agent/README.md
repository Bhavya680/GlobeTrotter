# GlobeTrotter AI Travel Assistant

This project is the AI Travel Assistant for the GlobeTrotter travel planning web application. 
It uses the Google Agent Development Kit (ADK) to provide a multi-agent architecture that integrates seamlessly with the existing PHP backend via REST APIs.

## Architecture

```text
User Request
    |
    v
+------------------------+
| Root Orchestrator      | (globetrotter_assistant)
| - Understands Context  |
| - Routes Requests      |
+------------------------+
    |
    +--> [Trip Planner] (create, edit, list trips)
    +--> [Destination Expert] (cities, activities)
    +--> [Budget Advisor] (costs, breakdowns)
    +--> [Itinerary Builder] (day-by-day scheduling)
    +--> [Community Guide] (posts, likes, comments)
    +--> [Admin Analyst] (insights, management)

Each agent relies on OKF (Open Knowledge Format) for static facts
and hits the PHP backend via shared tools for dynamic/user data.
```

## Prerequisites
- **Python:** 3.11+
- **Backend:** GlobeTrotter PHP app running locally (e.g., on XAMPP)

## Setup Steps
1. **Clone the repository:**
   `git clone <repository_url>`
2. **Install dependencies:**
   `pip install poetry`
   `poetry install`
3. **Configure Environment:**
   Copy `.env.example` to `.env` and fill in your keys.
4. **Run the Agent Server:**
   `adk web` (or `poetry run adk web`)

## OKF (Open Knowledge Format)
The OKF is the long-term, static memory for the agents. 
- Location: `globetrotter_agent/okf/`
- Structure: Bundled in folders (e.g., `destinations/`, `budget_intelligence/`).
- To add new knowledge, simply add a new `.md` file with YAML frontmatter inside the appropriate bundle folder. The `knowledge_loader.py` will automatically parse it on startup.

## Authentication Flow
The AI assistant runs completely separately from the PHP backend. When communicating with the API:
1. The web interface reads the user's `PHPSESSID` cookie.
2. The UI passes this cookie into the ADK Session State.
3. The `api_client.py` attaches it to all outgoing REST requests as `Cookie: PHPSESSID=...` ensuring the backend properly identifies and authorizes the user.

## Testing
Run the test suite via pytest:
```bash
pytest tests/
```

## Expanding the Agent
To add a new tool or sub-agent:
1. **Agent:** Create a new folder in `sub_agents/`, add `agent.py`, and link it to the Root Orchestrator.
2. **Tool:** Define the tool in `tools/`, document it thoroughly with a Google-style docstring, and attach it to the relevant agent.

## Deployment to Vertex AI
When moving to production, update your `.env`:
- Set `GOOGLE_GENAI_USE_VERTEXAI=TRUE`
- Set `SESSION_BACKEND=vertexai`
Deploy the container via Google Cloud Run or Vertex AI Agent Engine. Ensure that `GLOBETROTTER_API_BASE_URL` is pointing to the production PHP backend instead of `localhost`.
