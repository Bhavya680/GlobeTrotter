import streamlit as st
import requests
import re
import json

st.set_page_config(page_title="GlobeTrotter Tester", page_icon="🌍", layout="wide")

st.title("🌍 GlobeTrotter Backend Tester")
st.markdown("This utility acts as an independent client to test your pure PHP backend logic.")

# Initialize a persistent requests session to handle cookies (PHP Session ID)
if 'http_session' not in st.session_state:
    st.session_state.http_session = requests.Session()

st.sidebar.header("Configuration")
BASE_URL = st.sidebar.text_input("Local Server Base URL", "http://localhost/globetrotter")

def get_csrf_token(url):
    """Fetches a page and extracts the CSRF token using Regex."""
    try:
        res = st.session_state.http_session.get(url)
        match = re.search(r'name="csrf_token" value="([^"]+)"', res.text)
        return match.group(1) if match else ""
    except Exception as e:
        st.sidebar.error(f"Failed to connect: {e}")
        return ""

tab1, tab2, tab3 = st.tabs(["1. Registration Test", "2. Login Test", "3. Profile API Test"])

# --- TAB 1: REGISTRATION ---
with tab1:
    st.header("Test register.php")
    with st.form("register_form"):
        col1, col2 = st.columns(2)
        r_fname = col1.text_input("First Name", "Test")
        r_lname = col2.text_input("Last Name", "User")
        r_email = col1.text_input("Email", "testuser@example.com")
        r_phone = col2.text_input("Phone", "1234567890")
        r_pass = col1.text_input("Password", "Password123", type="password")
        r_conf = col2.text_input("Confirm Password", "Password123", type="password")
        
        submitted = st.form_submit_button("Submit Registration")
        if submitted:
            with st.spinner("Fetching CSRF token..."):
                csrf = get_csrf_token(f"{BASE_URL}/register.php")
            
            if not csrf:
                st.error("Could not find CSRF token on the registration page.")
            else:
                payload = {
                    'csrf_token': csrf,
                    'first_name': r_fname,
                    'last_name': r_lname,
                    'email': r_email,
                    'phone': r_phone,
                    'password': r_pass,
                    'confirm_password': r_conf
                }
                with st.spinner("Submitting POST request..."):
                    res = st.session_state.http_session.post(f"{BASE_URL}/register.php", data=payload)
                    
                    st.subheader("Response Data")
                    st.text(f"Status Code: {res.status_code}")
                    # Checking if we got redirected to dashboard
                    if "dashboard.php" in res.url or "dashboard" in res.text.lower():
                        st.success("✅ Registration appeared successful (Redirected to Dashboard)!")
                    else:
                        st.warning("Registration might have failed. See HTML output below.")
                        with st.expander("View Raw HTML Response"):
                            st.code(res.text, language="html")

# --- TAB 2: LOGIN ---
with tab2:
    st.header("Test login.php")
    with st.form("login_form"):
        l_email = st.text_input("Email", "testuser@example.com")
        l_pass = st.text_input("Password", "Password123", type="password")
        
        submitted = st.form_submit_button("Submit Login")
        if submitted:
            with st.spinner("Fetching CSRF token..."):
                csrf = get_csrf_token(f"{BASE_URL}/login.php")
                
            if not csrf:
                st.error("Could not find CSRF token on the login page.")
            else:
                payload = {
                    'csrf_token': csrf,
                    'email': l_email,
                    'password': l_pass
                }
                with st.spinner("Submitting POST request..."):
                    res = st.session_state.http_session.post(f"{BASE_URL}/login.php", data=payload)
                    
                    st.subheader("Response Data")
                    st.text(f"Status Code: {res.status_code}")
                    if "dashboard.php" in res.url or "dashboard" in res.text.lower():
                        st.success("✅ Login successful! Session established.")
                        # Check cookies
                        st.write("Current Session Cookies:", dict(st.session_state.http_session.cookies))
                    else:
                        st.error("Login failed. See HTML output below.")
                        with st.expander("View Raw HTML Response"):
                            st.code(res.text, language="html")

# --- TAB 3: PROFILE API ---
with tab3:
    st.header("Test api/profile.php")
    st.markdown("Requires an active session (Login first in Tab 2).")
    
    if st.button("GET Profile Data"):
        with st.spinner("Fetching profile..."):
            res = st.session_state.http_session.get(f"{BASE_URL}/api/profile.php")
            try:
                data = res.json()
                if data.get('success'):
                    st.success("Profile fetched successfully!")
                    st.json(data)
                else:
                    st.error(f"API Error: {data.get('message', 'Unknown error')}")
            except json.JSONDecodeError:
                st.error("Failed to decode JSON. Are you logged in?")
                st.code(res.text)
