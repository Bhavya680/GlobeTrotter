"""Admin tools for the GlobeTrotter agent."""
from ..shared.api_client import APIClient
from ..shared.session_state import get_session, is_admin

class PermissionError(Exception): pass

def _client() -> APIClient:
    if not is_admin():
        raise PermissionError("Admin access required for this tool.")
    return APIClient(session_cookie=get_session().session_cookie)

def get_all_users(page: int = 1, search: str = None, sort: str = None) -> list:
    """ADMIN ONLY. Retrieves all registered users."""
    params = {"action": "users", "page": page}
    if search: params["search"] = search
    if sort: params["sort"] = sort
    res = _client().get("admin.php", params=params)
    return res.get("data", [])

def toggle_user_role(user_id: int) -> tuple:
    """ADMIN ONLY. Toggles a user's role between 'user' and 'admin'."""
    res = _client().post("admin.php", params={"action": "toggle_role"}, json={"user_id": user_id})
    return res.get("new_role", "user"), res.get("message", "Role toggled")

def delete_user(user_id: int) -> tuple:
    """ADMIN ONLY. Permanently deletes a user and all their data."""
    res = _client().delete("admin.php", params={"user_id": user_id})
    return True, res.get("message", "Deleted user")

def get_analytics(time_range: str = '12months') -> dict:
    """ADMIN ONLY. Retrieves aggregated platform analytics."""
    return _client().get("admin.php", params={"action": "analytics", "range": time_range})

def get_popular_cities(limit: int = 10) -> list:
    """ADMIN ONLY. Returns cities ranked by how often they appear in trip stops."""
    res = _client().get("admin.php", params={"action": "popular_cities", "limit": limit})
    return res.get("data", [])
