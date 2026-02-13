#!/usr/bin/env python3
import cgi
import json
import os
import sys

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(SCRIPT_DIR, "cli", "venv", "lib",
                f"python{sys.version_info.major}.{sys.version_info.minor}",
                "site-packages"))

import mysql.connector

CONFIG_PATH = os.path.join(SCRIPT_DIR, "cli", "config.json")
DEFAULT_HRS = 72

params = cgi.FieldStorage()
try:
    hrs = int(params.getfirst("hrs", DEFAULT_HRS))
except (ValueError, TypeError):
    hrs = DEFAULT_HRS

with open(CONFIG_PATH) as f:
    config = json.load(f)

try:
    conn = mysql.connector.connect(
        host=config["db_host"],
        user=config["db_user"],
        password=config["db_pass"],
        database=config["db_name"],
    )
    cursor = conn.cursor()
    cursor.execute(
        "SELECT * FROM data WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %s HOUR) ORDER BY timestamp",
        (hrs,),
    )

    print("Content-Type: text/tab-separated-values")
    print()
    print("timestamp\theating\ttarget\tcurrent\thumidity\tupdated")
    for row in cursor:
        print("\t".join(str(col) for col in row))

    cursor.close()
    conn.close()
except Exception as e:
    print("Content-Type: text/plain")
    print("Status: 500 Internal Server Error")
    print()
    print(f"DB connection error: {e}")
