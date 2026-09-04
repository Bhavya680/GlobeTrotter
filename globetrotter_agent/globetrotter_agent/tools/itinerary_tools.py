"""Itinerary building and scheduling tools for the GlobeTrotter agent."""
from ..shared.api_client import APIClient
from ..shared.session_state import get_session

def _client() -> APIClient:
    return APIClient(session_cookie=get_session().session_cookie)

def add_trip_stop(trip_id: int, city_id: int, arrival_date: str, departure_date: str, notes: str = None, budget: float = None) -> tuple:
    """Adds a new stop (city visit) to an existing trip."""
    payload = {
        "trip_id": trip_id, "city_id": city_id, "arrival_date": arrival_date,
        "departure_date": departure_date, "notes": notes, "budget": budget
    }
    res = _client().post("stops.php", json=payload)
    return res.get("stop_id"), res.get("city_name"), "date_range", res.get("message", "Stop added")

def update_trip_stop(stop_id: int, **kwargs) -> tuple:
    """Updates details of an existing trip stop."""
    payload = {"stop_id": stop_id, **kwargs}
    res = _client().put("stops.php", json=payload)
    return True, res.get("message", "Stop updated")

def delete_trip_stop(stop_id: int) -> tuple:
    """Removes a stop and all its activities from a trip."""
    res = _client().delete("stops.php", params={"stop_id": stop_id})
    return True, res.get("message", "Stop deleted")

def reorder_stops(stop_order: list) -> tuple:
    """Updates the display order of all stops in a trip."""
    res = _client().post("stops.php", params={"action": "reorder"}, json={"stop_order": stop_order})
    return True, res.get("message", "Reordered")

def add_activity_to_stop(stop_id: int, activity_id: int, scheduled_date: str = None, scheduled_time: str = None, custom_cost: float = None, notes: str = None) -> tuple:
    """Adds an activity to a specific trip stop with optional scheduling info."""
    payload = {
        "stop_id": stop_id, "activity_id": activity_id, "scheduled_date": scheduled_date,
        "scheduled_time": scheduled_time, "custom_cost": custom_cost, "notes": notes
    }
    res = _client().post("stops.php", params={"action": "add_activity"}, json=payload)
    return res.get("trip_activity_id"), res.get("activity_name"), res.get("message", "Activity added")

def remove_activity_from_stop(trip_activity_id: int) -> tuple:
    """Removes a scheduled activity from a trip stop."""
    res = _client().delete("stops.php", params={"action": "remove_activity", "id": trip_activity_id})
    return True, res.get("message", "Removed")

def get_itinerary(trip_id: int) -> dict:
    """Retrieves the complete itinerary for a trip organized by day."""
    return _client().get("trips.php", params={"id": trip_id, "include_itinerary": "true"})
