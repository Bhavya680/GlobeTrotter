"""Community engagement tools for the GlobeTrotter agent."""
from ..shared.api_client import APIClient
from ..shared.session_state import get_session

def _client() -> APIClient:
    return APIClient(session_cookie=get_session().session_cookie)

def get_community_posts(page: int = 1, search: str = None, sort: str = None, group_by: str = None, tags: list = None) -> list:
    """Retrieves community posts with optional filtering, sorting, and search."""
    params = {"page": page}
    if search: params["search"] = search
    if sort: params["sort"] = sort
    if group_by: params["group_by"] = group_by
    if tags: params["tags"] = ",".join(tags)
    res = _client().get("community.php", params=params)
    return res.get("data", [])

def create_community_post(title: str, content: str, trip_id: int = None, tags: list = None) -> tuple:
    """Creates a new community post."""
    payload = {"title": title, "content": content}
    if trip_id: payload["trip_id"] = trip_id
    if tags: payload["tags"] = tags
    res = _client().post("community.php", json=payload)
    return res.get("post_id"), res.get("message", "Created")

def toggle_post_like(post_id: int) -> tuple:
    """Toggles a like on a community post."""
    res = _client().post("community.php", params={"action": "like"}, json={"post_id": post_id})
    return res.get("liked", True), res.get("new_like_count", 1), res.get("message", "Toggled like")

def add_comment(post_id: int, content: str) -> tuple:
    """Adds a comment to a community post."""
    res = _client().post("community.php", params={"action": "comment"}, json={"post_id": post_id, "content": content})
    return res.get("comment_id"), res.get("message", "Comment added")

def get_post_comments(post_id: int) -> list:
    """Retrieves all comments for a specific community post."""
    res = _client().get("community.php", params={"action": "comments", "post_id": post_id})
    return res.get("data", [])

def delete_community_post(post_id: int) -> tuple:
    """Deletes a community post. Users can only delete their own posts."""
    res = _client().delete("community.php", params={"post_id": post_id})
    return True, res.get("message", "Deleted")
