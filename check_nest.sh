#!/bin/bash

# This script will only work if it's running on a host configured to send
# mail using the 'mail' command. It also assumes you have 'grep'.

# Uncomment and populate if you want to send results to email rather than
# to the console
# RECIPIENTS=('your_email1@example.com' 'your_email2@example.com')

# Uncomment and populate if you want to override the email sender
# SENDER='your_from_email@example.com'

if [ -z "${SENDER}" ]; then
  MAILFROM=""
else
  MAILFROM="-r ${SENDER}"
fi

function creds_valid() {
  if [[ "${1}" == *"invalid user credentials"* ]]; then
    false
  else
    true
  fi
}

function device_online() {
  if [[ "${1}" == *"[online] => 1"* ]]; then
    true
  else
    false
  fi
}

function last_connection() {
  echo -e "${1}" | grep -o -E "\[last_connection\] => .{19}" | cut -c22-
}

SCRIPT=$(basename "$0")
DIR=$(cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)
DEV_INFO=$(php $DIR/device_info.php 2>&1 | sed -e 's/^[[:space:]]*//')
LAST=$(last_connection "${DEV_INFO}")

if [ -z "${DEV_INFO}" ]; then
  exit 1
fi

if creds_valid "${DEV_INFO}"; then
  if ! device_online "${DEV_INFO}"; then
    STATUS="Nest device is offline, last seen ${LAST}"
    if [[ -n "${RECIPIENTS[@]}" ]]; then
      for recip in "${RECIPIENTS[@]}"; do
	echo "${STATUS}" | mail ${MAILFROM} -s"${SCRIPT}: device offline" ${recip}
      done
    else
      echo "${STATUS}"
    fi
  fi
else
  STATUS="Nestgraph credential cache has expired"
  if [[ -n "${RECIPIENTS[@]}" ]]; then
    for recip in "${RECIPIENTS[@]}"; do
      echo "${STATUS}" | mail ${MAILFROM} -s"${SCRIPT}: expired session credentials" ${recip}
    done
  else
    echo "${STATUS}"    
  fi
fi

