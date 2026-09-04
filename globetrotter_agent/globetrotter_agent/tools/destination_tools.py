"""Destination and activity search tools for the GlobeTrotter agent."""
from ..shared.api_client import APIClient
from ..shared.session_state import get_session

def _client() -> APIClient:
    return APIClient(session_cookie=get_session().session_cookie)

def search_cities(query: str = None, region: str = None, cost_min: float = None, cost_max: float = None, sort: str = None, page: int = 1) -> list:
    """Searches for cities matching the given query and filter parameters."""
    params = {"page": page}
    if query: params["search"] = query
    if region: params["region"] = region
    if cost_min is not None: params["cost_min"] = cost_min
    if cost_max is not None: params["cost_max"] = cost_max
    if sort: params["sort"] = sort
    res = _client().get("cities.php", params=params)
    return res.get("data", [])

def get_city_details(city_id: int) -> dict:
    """Retrieves full information about a specific city including all available activities."""
    return _client().get("cities.php", params={"id": city_id})

def search_activities(query: str = None, city_id: int = None, category: str = None, cost_min: float = None, cost_max: float = None, page: int = 1) -> list:
    """Searches for activities with optional filtering."""
    params = {"page": page}
    if query: params["search"] = query
    if city_id is not None: params["city_id"] = city_id
    if category: params["category"] = category
    if cost_min is not None: params["cost_min"] = cost_min
    if cost_max is not None: params["cost_max"] = cost_max
    res = _client().get("activities.php", params=params)
    return res.get("data", [])

def get_activity_details(activity_id: int) -> dict:
    """Retrieves complete details for a specific activity."""
    return _client().get("activities.php", params={"id": activity_id})

def save_destination(city_id: int) -> tuple:
    """Saves a city to the current user's saved destinations list."""
    res = _client().post("profile.php", params={"action": "save_destination"}, json={"city_id": city_id})
    return True, res.get("message", "Saved")
