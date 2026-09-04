"""HTTP client wrapper for PHP APIs."""
import os
import requests
from typing import Dict, Any, Optional

class AuthError(Exception): pass
class PermissionError(Exception): pass
class NotFoundError(Exception): pass
class ServerError(Exception): pass
class ValidationError(Exception): pass

class APIClient:
    """Wrapper for all HTTP calls to the GlobeTrotter PHP backend."""
    
    def __init__(self, session_cookie: Optional[str] = None):
        self.base_url = os.environ.get("GLOBETROTTER_API_BASE_URL", "http://localhost/globetrotter/api").rstrip('/')
        self.session_cookie = session_cookie
        self.timeout = 10
        self.session = requests.Session()
        
    def _get_headers(self) -> Dict[str, str]:
        headers = {"Content-Type": "application/json"}
        if self.session_cookie:
            headers["Cookie"] = f"PHPSESSID={self.session_cookie}"
        return headers

    def _handle_response(self, response: requests.Response) -> Any:
        try:
            data = response.json()
        except ValueError:
            data = None
            
        if not response.ok:
            error_msg = data.get("message", "Unknown error") if data else response.reason
            if response.status_code == 401:
                raise AuthError(error_msg)
            elif response.status_code == 403:
                raise PermissionError(error_msg)
            elif response.status_code == 404:
                raise NotFoundError(error_msg)
            elif response.status_code == 400:
                raise ValidationError(error_msg)
            else:
                raise ServerError(f"[{response.status_code}] {error_msg}")
                
        if data is None:
            raise ServerError(f"Invalid JSON response from server: {response.text}")
                
        # Some APIs return success flag inside JSON
        if isinstance(data, dict) and data.get("success") is False:
            error_msg = data.get("message", "Unknown application error")
            raise ValidationError(error_msg)
            
        return data

    def _request(self, method: str, endpoint: str, **kwargs) -> Any:
        url = f"{self.base_url}/{endpoint.lstrip('/')}"
        headers = self._get_headers()
        
        for attempt in range(2):
            try:
                response = self.session.request(
                    method=method, 
                    url=url, 
                    headers=headers, 
                    timeout=self.timeout,
                    **kwargs
                )
                return self._handle_response(response)
            except requests.exceptions.Timeout:
                if attempt == 0:
                    continue # Retry once on timeout
                raise ServerError("API request timed out after retry.")
            except AuthError:
                raise # No retry on auth error
            except requests.exceptions.RequestException as e:
                raise ServerError(f"Request failed: {str(e)}")

    def get(self, endpoint: str, params: Optional[Dict[str, Any]] = None) -> Any:
        return self._request("GET", endpoint, params=params)

    def post(self, endpoint: str, json: Optional[Dict[str, Any]] = None) -> Any:
        return self._request("POST", endpoint, json=json)
        
    def put(self, endpoint: str, json: Optional[Dict[str, Any]] = None) -> Any:
        return self._request("PUT", endpoint, json=json)
        
    def delete(self, endpoint: str, params: Optional[Dict[str, Any]] = None) -> Any:
        return self._request("DELETE", endpoint, params=params)
