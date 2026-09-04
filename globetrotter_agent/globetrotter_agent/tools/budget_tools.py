"""Budget tracking and estimation tools for the GlobeTrotter agent."""
from ..shared.api_client import APIClient
from ..shared.session_state import get_session
from ..shared.knowledge_loader import get_knowledge

def _client() -> APIClient:
    return APIClient(session_cookie=get_session().session_cookie)

def get_trip_budget(trip_id: int) -> dict:
    """Retrieves the budget plan and actual spending for a specific trip."""
    return _client().get("budget.php", params={"trip_id": trip_id})

def save_trip_budget(trip_id: int, transport: float, stay: float, activities: float, meals: float, misc: float) -> tuple:
    """Saves or updates the budget allocation for a trip across all 5 categories."""
    payload = {
        "trip_id": trip_id, "transport": transport, "stay": stay,
        "activities": activities, "meals": meals, "misc": misc
    }
    res = _client().post("budget.php", json=payload)
    return True, res.get("total_budget", 0), res.get("message", "Budget saved")

def get_budget_vs_actual(trip_id: int) -> dict:
    """Returns a comparison of budgeted vs actual spending for a trip."""
    return _client().get("budget.php", params={"trip_id": trip_id, "include_actual": "true"})

def estimate_trip_cost(city_ids: list, days_per_city: list, traveler_tier: str) -> dict:
    """Estimates the total cost using OKF benchmark data."""
    # Dummy implementation representing reading OKF data for cost logic
    benchmarks = get_knowledge("budget_intelligence", "daily_cost_benchmarks")
    # Logic parsing benchmark string omitted in mock tool
    total_days = sum(days_per_city)
    multiplier = {"budget": 50, "mid-range": 150, "luxury": 400}.get(traveler_tier.lower(), 100)
    total_estimate = total_days * multiplier
    return {"total_estimate": total_estimate, "per_city_estimate": []}
