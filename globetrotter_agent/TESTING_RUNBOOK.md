# Local Testing Runbook for GlobeTrotter AI Assistant

This step-by-step guide walks you through setting up and validating the ADK agent on your local machine, connecting it to your active XAMPP/PHP backend.

## Step 1: Environment Setup
1. **Open your terminal** and navigate to the project directory:
   ```bash
   cd globetrotter_agent
   ```
2. **Create a virtual environment (optional but recommended):**
   ```bash
   python -m venv venv
   # Windows:
   venv\Scripts\activate
   # Mac/Linux:
   source venv/bin/activate
   ```
3. **Install the required dependencies** using `pip` (or `poetry install` if using Poetry):
   ```bash
   pip install google-adk python-dotenv requests python-frontmatter pyyaml
   ```

## Step 2: Configuration
1. Rename `.env.example` to `.env` if you haven't already.
2. Open `.env` and configure your credentials:
   ```env
   # Your local PHP backend
   GLOBETROTTER_API_BASE_URL=http://localhost/globetrotter/api
   
   # Your Gemini API Key from Google AI Studio
   GOOGLE_API_KEY=AIzaSy...
   
   # Standard configurations
   GOOGLE_GENAI_USE_VERTEXAI=FALSE
   SESSION_BACKEND=memory
   LOG_LEVEL=DEBUG
   ```

## Step 3: Launch
1. **Start your PHP Backend:**
   Open the XAMPP Control Panel and ensure both **Apache** and **MySQL** are running. Verify your backend is accessible at `http://localhost/globetrotter/api`.
2. **Start the ADK Agent Interface:**
   In your terminal (with the venv activated), run the ADK web console:
   ```bash
   adk web
   ```
   *This starts the agent on a local port (usually `http://localhost:8080`). Open that URL in your browser to interact with GlobeBot.*

---

## Step 4: Test Scenarios

Type the following prompts into the ADK web interface to verify both OKF reading and PHP API tool execution.

### Scenario 1: OKF Knowledge Retrieval
**Prompt:** `"What is the best time to visit Tokyo, and what are some cultural tips?"`
**Expected Result:** The Root Orchestrator delegates to the **Destination Expert**. The agent reads the OKF markdown for Tokyo without hitting the API and responds with structured advice based purely on the `destinations` bundle.

### Scenario 2: Simple Tool Routing (PHP API)
**Prompt:** `"Can you show me a list of all my upcoming trips?"`
**Expected Result:** The Root Orchestrator delegates to the **Trip Planner**. The agent calls `get_my_trips(status='upcoming')`. Since `adk web` might not pass a valid `PHPSESSID` cookie natively, you might see the agent gracefully handle a mock `AuthError` asking you to log in, proving the API integration is alive!

### Scenario 3: Complex Scheduling (Itinerary Builder)
**Prompt:** `"I want to plan a 3-day itinerary for my existing trip to Paris. Please add some morning and afternoon activities."`
**Expected Result:** The Root Orchestrator delegates to the **Itinerary Builder**. The agent utilizes the OKF `duration_and_pacing.md` rules (max 2-3 activities per day) and calls the `add_activity_to_stop` tool repeatedly for Paris.

### Scenario 4: Budget Constraints (Budget Advisor)
**Prompt:** `"Help me estimate the cost for a 5-day luxury trip to Dubai."`
**Expected Result:** The Root Orchestrator delegates to the **Budget Advisor**. The agent uses the OKF `daily_cost_benchmarks` to calculate an estimate and responds without hitting the API, proving its hybrid capabilities.

### Scenario 5: Community Engagement (Community Guide)
**Prompt:** `"I want to write a post about my budget tips in Rome. Can you help me draft it and post it?"`
**Expected Result:** The Root Orchestrator delegates to the **Community Guide**. The agent asks clarifying questions to ensure the post is >20 characters and <2000 characters based on OKF rules, and then uses the `create_community_post` API tool to submit the JSON payload.
