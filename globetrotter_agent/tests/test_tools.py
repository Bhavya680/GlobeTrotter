"""Test specifications for API tools."""
import pytest
from unittest.mock import patch, MagicMock
from globetrotter_agent.shared.api_client import APIClient, ValidationError
from globetrotter_agent.tools.trip_tools import create_trip
from globetrotter_agent.tools.admin_tools import get_analytics, PermissionError
from globetrotter_agent.shared.session_state import load_session

@pytest.fixture(autouse=True)
def setup_session():
    load_session({"user_name": "test_user", "current_screen": "test_screen", "user_role": "admin", "session_cookie": "mock_phpsessid"})

@patch('globetrotter_agent.shared.api_client.requests.Session.request')
def test_create_trip_success(mock_request):
    """Mock 200 response, verify returns trip_id."""
    mock_resp = MagicMock()
    mock_resp.status_code = 200
    mock_resp.json.return_value = {"success": True, "data": {"trip_id": 123}}
    mock_request.return_value = mock_resp

    result = create_trip("Test Trip", "2024-01-01", "2024-01-10")
    print(f"\n[OUTPUT] create_trip returned: {result}")
    assert result["data"]["trip_id"] == 123

@patch('globetrotter_agent.shared.api_client.requests.Session.request')
def test_create_trip_validation_error(mock_request):
    """Mock 400 response, verify raises ValidationError."""
    mock_resp = MagicMock()
    mock_resp.status_code = 400
    mock_resp.json.return_value = {"success": False, "message": "Invalid dates"}
    mock_request.return_value = mock_resp

    with pytest.raises(ValidationError) as exc_info:
        create_trip("Test Trip", "invalid", "invalid")
    print(f"\n[OUTPUT] create_trip threw validation error as expected: {exc_info.value}")

@patch('globetrotter_agent.shared.api_client.requests.Session.request')
def test_admin_tool_blocked_for_non_admin(mock_request):
    """Mock non-admin session, verify PermissionError."""
    load_session({"user_name": "user1", "current_screen": "home", "user_role": "user", "session_cookie": "mock_phpsessid"})
    with pytest.raises(PermissionError) as exc_info:
        get_analytics("12months")
    print(f"\n[OUTPUT] get_analytics threw permission error as expected: {exc_info.value}")

@patch('globetrotter_agent.shared.api_client.requests.Session.request')
def test_api_client_retry_on_timeout(mock_request):
    """Verify one retry happens on timeout."""
    import requests
    mock_request.side_effect = [requests.exceptions.Timeout("Timeout"), MagicMock(status_code=200, json=lambda: {"success": True, "data": {}})]
    
    client = APIClient()
    resp = client.get("test")
    print(f"\n[OUTPUT] API client successfully recovered from timeout and returned: {resp}")
    assert mock_request.call_count == 2
