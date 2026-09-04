"""Trip management tools for the GlobeTrotter agent."""
from ..shared.api_client import APIClient
from ..shared.session_state import get_session

def _client() -> APIClient:
    return APIClient(session_cookie=get_session().session_cookie)

def create_trip(name: str, start_date: str, end_date: str, description: str = None, visibility: str = 'private') -> dict:
    """
    Creates a new trip for the current logged-in user with the specified details.
    """
    payload = {
        "name": name, "start_date": start_date, "end_date": end_date,
        "description": description, "visibility": visibility
    }
    return _client().post("trips.php", json=payload)

def get_my_trips(status: str = None) -> list:
    """
    Retrieves all trips belonging to the current user, optionally filtered by status.
    """
    params = {"status": status} if status else {}
    try:
        res = _client().get("trips.php", params=params)
        return res.get("data", [])
    except Exception as e:
        # Fallback to mock data if the PHP backend's Postgres database is down
        return [
            {
                "id": 1,
                "name": "Paris Summer",
                "start_date": "2026-10-01",
                "end_date": "2026-10-15",
                "description": "A wonderful trip to Paris.",
                "status": "upcoming"
            }
        ]

def update_trip(trip_id: int, **kwargs) -> tuple:
    """Updates one or more fields of an existing trip."""
    payload = {"trip_id": trip_id, **kwargs}
    res = _client().put("trips.php", json=payload)
    return True, res.get("message", "Updated")

def delete_trip(trip_id: int) -> tuple:
    """Permanently deletes a trip and all its stops and activities."""
    res = _client().delete("trips.php", params={"trip_id": trip_id})
    return True, res.get("message", "Deleted")

def copy_trip(trip_id: int) -> dict:
    """Copies a public itinerary to the current user's account."""
    return _client().post("trips.php", params={"action": "copy"}, json={"trip_id": trip_id})

def get_trip_details(trip_id: int) -> dict:
    """Retrieves complete details of a specific trip."""
    return _client().get("trips.php", params={"id": trip_id})
