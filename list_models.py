import os
import requests
from dotenv import load_dotenv

load_dotenv(r"C:\Users\Admin\Desktop\Globetrotter\globetrotter_agent\.env")
api_key = os.getenv("GOOGLE_API_KEY")

url = f"https://generativelanguage.googleapis.com/v1beta/models?key={api_key}"
response = requests.get(url)

if response.ok:
    data = response.json()
    print("Available Models supporting generateContent:")
    for model in data.get("models", []):
        name = model.get("name")
        version = model.get("version")
        supported = model.get("supportedGenerationMethods", [])
        if "generateContent" in supported:
            print(f"- {name} (Version: {version})")
else:
    print(f"Error: {response.status_code} - {response.text}")
