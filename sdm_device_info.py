#!/usr/bin/env python3
"""List Nest devices and their traits via the Google SDM API.

Used for debugging and by check_nest.sh to verify device connectivity.
"""

import argparse
import json
import os
import sys

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
        print(f"Error: Failed to refresh access token ({resp.status_code}):")
        print(resp.text)
        sys.exit(1)
    return resp.json()["access_token"]


def list_devices(config, access_token):
    """List all devices in the SDM project."""
    url = f"{SDM_API_URL}/enterprises/{config['sdm_project_id']}/devices"
    resp = requests.get(url, headers={"Authorization": f"Bearer {access_token}"})
    if resp.status_code != 200:
        print(f"Error: Failed to list devices ({resp.status_code}):")
        print(resp.text)
        sys.exit(1)
    return resp.json()


def main():
    parser = argparse.ArgumentParser(description="List Nest devices via the Google SDM API")
    parser.add_argument("-v", "--verbose", action="store_true",
                        help="Display full API response for each device")
    args = parser.parse_args()

    config = load_config()

    if not config.get("refresh_token"):
        print("Error: No refresh token found. Run sdm_auth.py first.")
        sys.exit(1)

    access_token = get_access_token(config)
    data = list_devices(config, access_token)

    devices = data.get("devices", [])
    if not devices:
        print("No devices found.")
        sys.exit(0)

    for device in devices:
        traits = device.get("traits", {})
        device_type = device.get("type", "Unknown")
        device_name = device.get("name", "Unknown")

        print(f"Device: {device_name}")
        print(f"  Type: {device_type}")

        if args.verbose:
            # Print all traits with readable names
            for trait_name, trait_data in sorted(traits.items()):
                short_name = trait_name.replace("sdm.devices.traits.", "")
                print(f"  {short_name}:")
                for key, value in trait_data.items():
                    print(f"    {key}: {value}")
            if "parentRelations" in device:
                print("  Parent Relations:")
                for rel in device["parentRelations"]:
                    print(f"    parent: {rel.get('parent', 'Unknown')}")
                    print(f"    displayName: {rel.get('displayName', 'Unknown')}")
        else:
            connectivity = traits.get("sdm.devices.traits.Connectivity", {})
            if connectivity:
                print(f"  Connectivity: {connectivity.get('status', 'UNKNOWN')}")

            temperature = traits.get("sdm.devices.traits.Temperature", {})
            if temperature:
                celsius = temperature.get("ambientTemperatureCelsius")
                if celsius is not None:
                    fahrenheit = celsius * 1.8 + 32
                    print(f"  Current Temperature: {fahrenheit:.1f}F ({celsius:.1f}C)")

            humidity = traits.get("sdm.devices.traits.Humidity", {})
            if humidity:
                print(f"  Humidity: {humidity.get('ambientHumidityPercent')}%")

            setpoint = traits.get("sdm.devices.traits.ThermostatTemperatureSetpoint", {})
            if setpoint:
                heat_c = setpoint.get("heatCelsius")
                cool_c = setpoint.get("coolCelsius")
                if heat_c is not None:
                    print(f"  Heat Setpoint: {heat_c * 1.8 + 32:.1f}F ({heat_c:.1f}C)")
                if cool_c is not None:
                    print(f"  Cool Setpoint: {cool_c * 1.8 + 32:.1f}F ({cool_c:.1f}C)")

            hvac = traits.get("sdm.devices.traits.ThermostatHvac", {})
            if hvac:
                print(f"  HVAC Status: {hvac.get('status', 'UNKNOWN')}")

            mode = traits.get("sdm.devices.traits.ThermostatMode", {})
            if mode:
                print(f"  Thermostat Mode: {mode.get('mode', 'UNKNOWN')}")

        print()


if __name__ == "__main__":
    main()
