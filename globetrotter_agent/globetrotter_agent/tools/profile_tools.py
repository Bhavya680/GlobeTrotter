"""Profile management tools for the GlobeTrotter agent."""
from ..shared.api_client import APIClient
from ..shared.session_state import get_session

def _client() -> APIClient:
    return APIClient(session_cookie=get_session().session_cookie)

def get_user_profile() -> dict:
    """Retrieves the complete profile of the currently logged-in user."""
    return _client().get("profile.php")

def update_profile(first_name: str = None, last_name: str = None, phone: str = None, city: str = None, country: str = None, additional_info: str = None) -> tuple:
    """Updates the current user's profile fields."""
    payload = {}
    if first_name: payload["first_name"] = first_name
    if last_name: payload["last_name"] = last_name
    if phone: payload["phone"] = phone
    if city: payload["city"] = city
    if country: payload["country"] = country
    if additional_info: payload["additional_info"] = additional_info
    res = _client().post("profile.php", params={"action": "update"}, json=payload)
    return True, res.get("updated_fields", {}), res.get("message", "Updated")

def change_password(current_password: str, new_password: str, confirm_password: str) -> tuple:
    """Changes the current user's password after verifying the current one."""
    payload = {"current_password": current_password, "new_password": new_password, "confirm_password": confirm_password}
    res = _client().post("profile.php", params={"action": "change_password"}, json=payload)
    return True, res.get("message", "Password changed")

def get_saved_destinations() -> list:
    """Retrieves the list of cities saved to the current user's profile."""
    res = _client().get("profile.php", params={"action": "saved_destinations"})
    return res.get("data", [])
