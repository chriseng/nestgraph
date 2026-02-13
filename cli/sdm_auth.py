#!/usr/bin/env python3
"""One-time OAuth2 setup for Google Smart Device Management API.

Run this script once to authorize nestgraph and store a refresh token
in config.json. The refresh token does not expire unless revoked.

Prerequisites:
  1. Register at https://console.nest.google.com/device-access
  2. Create a Google Cloud project with OAuth2 credentials (Web application type)
  3. Enable the SDM API in Google Cloud Console
  4. Add http://localhost:8080 as an authorized redirect URI in your OAuth client
  5. Fill in config.json with your project IDs and OAuth credentials
"""

import json
import os
import sys
import webbrowser
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlencode, urlparse, parse_qs

import requests

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_PATH = os.path.join(SCRIPT_DIR, "config.json")

AUTHORIZATION_URL = "https://nestservices.google.com/partnerconnections/{sdm_project_id}/auth"
TOKEN_URL = "https://www.googleapis.com/oauth2/v4/token"
REDIRECT_URI = "http://localhost:8080"
SCOPES = "https://www.googleapis.com/auth/sdm.service"


def load_config():
    with open(CONFIG_PATH) as f:
        return json.load(f)


def save_config(config):
    with open(CONFIG_PATH, "w") as f:
        json.dump(config, f, indent=4)
        f.write("\n")


class OAuthCallbackHandler(BaseHTTPRequestHandler):
    """HTTP handler that captures the OAuth authorization code from the redirect."""

    auth_code = None
    error = None

    def do_GET(self):
        query = parse_qs(urlparse(self.path).query)
        if "code" in query:
            OAuthCallbackHandler.auth_code = query["code"][0]
            self.send_response(200)
            self.send_header("Content-Type", "text/html")
            self.end_headers()
            self.wfile.write(b"<html><body><h2>Authorization successful!</h2>"
                             b"<p>You can close this tab and return to the terminal.</p>"
                             b"</body></html>")
        elif "error" in query:
            OAuthCallbackHandler.error = query["error"][0]
            self.send_response(400)
            self.send_header("Content-Type", "text/html")
            self.end_headers()
            self.wfile.write(f"<html><body><h2>Authorization failed: {query['error'][0]}</h2>"
                             f"</body></html>".encode())
        else:
            self.send_response(400)
            self.send_header("Content-Type", "text/html")
            self.end_headers()
            self.wfile.write(b"<html><body><h2>Unexpected response</h2></body></html>")

    def log_message(self, format, *args):
        pass  # Suppress request logging


def main():
    config = load_config()

    for key in ("sdm_project_id", "oauth_client_id", "oauth_client_secret"):
        if not config.get(key) or config[key].startswith("your-"):
            print(f"Error: Please set '{key}' in {CONFIG_PATH} before running this script.")
            sys.exit(1)

    # Build authorization URL
    auth_url = AUTHORIZATION_URL.format(sdm_project_id=config["sdm_project_id"])
    params = urlencode({
        "redirect_uri": REDIRECT_URI,
        "access_type": "offline",
        "prompt": "consent",
        "client_id": config["oauth_client_id"],
        "response_type": "code",
        "scope": SCOPES,
    })
    full_url = f"{auth_url}?{params}"

    # Start local server to receive the OAuth callback
    server = HTTPServer(("localhost", 8080), OAuthCallbackHandler)

    print("Opening browser for Google authorization...")
    print(f"\nIf the browser does not open, visit this URL manually:\n{full_url}\n")
    print("Waiting for authorization callback on http://localhost:8080 ...")
    webbrowser.open(full_url)

    # Handle a single request (the OAuth redirect)
    server.handle_request()
    server.server_close()

    if OAuthCallbackHandler.error:
        print(f"\nError: Authorization failed: {OAuthCallbackHandler.error}")
        sys.exit(1)

    auth_code = OAuthCallbackHandler.auth_code
    if not auth_code:
        print("\nError: No authorization code received.")
        sys.exit(1)

    # Exchange authorization code for tokens
    print("\nExchanging authorization code for tokens...")
    resp = requests.post(TOKEN_URL, data={
        "client_id": config["oauth_client_id"],
        "client_secret": config["oauth_client_secret"],
        "code": auth_code,
        "grant_type": "authorization_code",
        "redirect_uri": REDIRECT_URI,
    })

    if resp.status_code != 200:
        print(f"Error: Token exchange failed ({resp.status_code}):")
        print(resp.text)
        sys.exit(1)

    tokens = resp.json()
    if "refresh_token" not in tokens:
        print("Error: No refresh token in response. Try revoking access and re-authorizing.")
        print(json.dumps(tokens, indent=2))
        sys.exit(1)

    config["refresh_token"] = tokens["refresh_token"]
    save_config(config)

    print("\nSuccess! Refresh token saved to config.json.")
    print("You can now run sdm_device_info.py to verify connectivity,")
    print("then set up sdm_collect.py in your crontab.")


if __name__ == "__main__":
    main()
