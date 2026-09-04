"""Manages what GlobeBot knows about the current user's context."""
from dataclasses import dataclass
from typing import Optional, Dict, Any

@dataclass
class SessionContext:
    user_id: Optional[int] = None
    user_name: Optional[str] = None
    user_email: Optional[str] = None
    user_role: str = 'user'
    session_cookie: Optional[str] = None
    current_screen: Optional[str] = None
    active_trip_id: Optional[int] = None
    active_stop_id: Optional[int] = None
    last_search_query: Optional[str] = None
    conversation_turns: int = 0
    tool_call_count: int = 0

# Global session instance for the current run
_current_session = SessionContext()

def load_session(session_data: Dict[str, Any]) -> SessionContext:
    global _current_session
    _current_session = SessionContext(
        user_id=session_data.get("user_id"),
        user_name=session_data.get("user_name"),
        user_email=session_data.get("user_email"),
        user_role=session_data.get("user_role", "user"),
        session_cookie=session_data.get("session_cookie"),
        current_screen=session_data.get("current_screen"),
        active_trip_id=session_data.get("active_trip_id"),
        active_stop_id=session_data.get("active_stop_id"),
        last_search_query=session_data.get("last_search_query"),
        conversation_turns=session_data.get("conversation_turns", 0)
    )
    return _current_session

def get_session() -> SessionContext:
    return _current_session

def update_context(key: str, value: Any):
    if hasattr(_current_session, key):
        setattr(_current_session, key, value)

def clear_session():
    global _current_session
    _current_session = SessionContext()

def is_admin() -> bool:
    return _current_session.user_role == 'admin'

def has_active_trip() -> bool:
    return _current_session.active_trip_id is not None
