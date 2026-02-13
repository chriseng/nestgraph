#!/usr/bin/env python3
"""Collect Nest thermostat data via Google SDM API and insert into MySQL.

Replaces collect.php + insert.php. Intended to run from crontab every 5 minutes:
  */5 * * * * /usr/bin/python3 /path/to/sdm_collect.py >> /var/log/nestgraph.log 2>&1
"""

import json
import os
import sys
from datetime import datetime

import mysql.connector
import requests

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_PATH = os.path.join(SCRIPT_DIR, "config.json")

TOKEN_URL = "https://www.googleapis.com/oauth2/v4/token"
SDM_API_URL = "https://smartdevicemanagement.googleapis.com/v1"


def load_config():
    with open(CONFIG_PATH) as f:
        return json.load(f)


def get_access_token(config):
    """Use the refresh token to obtain a fresh access token."""
    resp = requests.post(TOKEN_URL, data={
        "client_id": config["oauth_client_id"],
        "client_secret": config["oauth_client_secret"],
        "refresh_token": config["refresh_token"],
        "grant_type": "refresh_token",
    })
    if resp.status_code != 200:
        print(f"{datetime.now()}: Error refreshing token ({resp.status_code}): {resp.text}",
              file=sys.stderr)
        sys.exit(1)
    return resp.json()["access_token"]


def get_thermostat_data(config, access_token):
    """Fetch all devices and return data from the first thermostat found."""
    url = f"{SDM_API_URL}/enterprises/{config['sdm_project_id']}/devices"
    resp = requests.get(url, headers={"Authorization": f"Bearer {access_token}"})
    if resp.status_code != 200:
        print(f"{datetime.now()}: Error listing devices ({resp.status_code}): {resp.text}",
              file=sys.stderr)
        sys.exit(1)

    devices = resp.json().get("devices", [])
    for device in devices:
        if "THERMOSTAT" in device.get("type", ""):
            return extract_traits(device)

    print(f"{datetime.now()}: Error: No thermostat device found.", file=sys.stderr)
    sys.exit(1)


def c_to_f(celsius):
    """Convert Celsius to Fahrenheit."""
    return celsius * 1.8 + 32


def extract_traits(device):
    """Extract relevant data from device traits."""
    traits = device.get("traits", {})

    # Current temperature
    temp_trait = traits.get("sdm.devices.traits.Temperature", {})
    current_c = temp_trait.get("ambientTemperatureCelsius")
    current_f = c_to_f(current_c) if current_c is not None else None

    # Target temperature (heat setpoint preferred, fall back to cool)
    setpoint_trait = traits.get("sdm.devices.traits.ThermostatTemperatureSetpoint", {})
    target_c = setpoint_trait.get("heatCelsius") or setpoint_trait.get("coolCelsius")
    target_f = c_to_f(target_c) if target_c is not None else None

    # Humidity
    humidity_trait = traits.get("sdm.devices.traits.Humidity", {})
    humidity = humidity_trait.get("ambientHumidityPercent")

    # HVAC status — 1 if heating, 0 otherwise
    hvac_trait = traits.get("sdm.devices.traits.ThermostatHvac", {})
    hvac_status = hvac_trait.get("status", "OFF")
    heating = 1 if hvac_status == "HEATING" else 0

    # Connectivity check
    conn_trait = traits.get("sdm.devices.traits.Connectivity", {})
    if conn_trait.get("status") == "OFFLINE":
        print(f"{datetime.now()}: Warning: Device is OFFLINE, data may be stale.",
              file=sys.stderr)

    return {
        "current": current_f,
        "target": target_f,
        "humidity": humidity,
        "heating": heating,
    }


def insert_data(config, data):
    """Insert thermostat data into the MySQL data table."""
    if data["current"] is None or data["target"] is None or data["humidity"] is None:
        print(f"{datetime.now()}: Error: Incomplete data, skipping insert: {data}",
              file=sys.stderr)
        sys.exit(1)

    conn = mysql.connector.connect(
        host=config["db_host"],
        user=config["db_user"],
        password=config["db_pass"],
        database=config["db_name"],
    )
    try:
        cursor = conn.cursor()
        cursor.execute(
            "REPLACE INTO data (timestamp, heating, target, current, humidity, updated) "
            "VALUES (NOW(), %s, %s, %s, %s, NOW())",
            (data["heating"], data["target"], data["current"], data["humidity"]),
        )
        conn.commit()
    finally:
        conn.close()


def main():
    config = load_config()

    if not config.get("refresh_token"):
        print("Error: No refresh token found. Run sdm_auth.py first.", file=sys.stderr)
        sys.exit(1)

    access_token = get_access_token(config)
    data = get_thermostat_data(config, access_token)
    insert_data(config, data)
    print(f"{datetime.now()}: Collected — "
          f"current={data['current']:.1f}F, target={data['target']:.1f}F, "
          f"humidity={data['humidity']}%, heating={data['heating']}")


if __name__ == "__main__":
    main()
