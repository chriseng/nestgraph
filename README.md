# nestgraph

Create pretty charts of your Nest thermostat data.

## Background

The goal of this project (in 2013) was to see how well the Nest algorithms work. In particuar, the Nest claims to minimize overshoot, which is a common problem with cast-iron radiators. It also claims to know when to start heating in order to hit your target temperature exactly at the time you scheduled it.  

Unfortunately, you can't actually access historical temperature data on the Nest website or via the iOS app. It shows you when heating was turned on/off and what the temperature targets were at those times, but it doesn't give you any indication of how well or how poorly the thermostat performed. This could be by design, as it's a lot of information to store. In order to take a closer look, we pull temperature readings periodically and store them in a database so that the data can be inspected in an easily consumable form.

I also wanted an excuse to play with the [D3](http://d3js.org) (Data-Driven Documents) library a little.

## History

Originally, this project used an unofficial [nest-api](https://github.com/gboudreau/nest-api) library by Guillaume Boudreau. However, starting in January 2020, the nest-api library became unable to authenticate directly to the Google Nest API due to impending plans to deprecate the Works With Nest program, which [officially ended](https://support.google.com/googlenest/answer/9293712?hl=en-419) in September 2023.

Thanks to a couple hours of assistance from Claude Code, this project now uses Google's official [Smart Device Management (SDM) API](https://developers.google.com/nest/device-access).

## Features

* Polls Google SDM API to collect thermostat telemetry
* Stores selected data in local MySQL database
* Generates a nice visualization of actual temp vs. set point
* Lower mini-chart is interactive pan-and-zoom of the upper chart
* Hover over the gray circles to get the exact timestamp and temperature

![nestgraph screenshot](https://raw.githubusercontent.com/chriseng/nestgraph/refs/heads/master/nestgraph-screenshot.png)

## Compatible Thermostats

All Nest thermostats linked to a Google account are supported via the [SDM API](https://developers.google.com/nest/device-access/api/thermostat):

* Nest Learning Thermostat (1st, 2nd, 3rd gen)
* Nest Thermostat E
* Nest Thermostat (2020)

## Dependencies

* Apache with `mod_cgi` enabled (serves the visualization and data endpoint)
* Python 3 with venv (data collection scripts and CGI endpoint)
* MySQL
* Google [Smart Device Management API](https://developers.google.com/nest/device-access) access ($5 one-time registration)

## Getting Started

### 1. Google API Setup

1. In [Google Cloud Console](https://console.cloud.google.com), create a project and enable the **Smart Device Management API**
2. Create **OAuth 2.0 credentials** (Web application type) and add `http://localhost:8080` as an authorized redirect URI
3. Configure the **OAuth consent screen** (External), add your Google account as a test user, and add the scope `https://www.googleapis.com/auth/sdm.service`. Then **publish the app**: under *Publishing status*, click **Publish App**. Apps left in Testing status issue refresh tokens that expire after 7 days; publishing to Production makes them last indefinitely (Google only revokes inactive tokens after 6 months, which won't happen if the cron job runs continuously). You do not need Google to verify your app — publishing is sufficient for personal use.
4. Register at [Google Device Access Console](https://console.nest.google.com/device-access) ($5 one-time fee) and create a project to get your **SDM Project ID**
5. In the Device Access Console, link your OAuth client ID to your SDM project

### 2. Enable Apache CGI

Enable `mod_cgi` if it isn't already:

```bash
sudo a2enmod cgi
sudo systemctl restart apache2
```

Ensure your Apache site configuration allows `.htaccess` overrides for the nestgraph directory (the repo includes an `.htaccess` that enables CGI for `.py` files):

```apache
<Directory /var/www/html/nestgraph>
    AllowOverride All
</Directory>
```

### 3. Clone and Configure

```bash
cd [your-web-root]
git clone https://github.com/chriseng/nestgraph.git
cd nestgraph/cli
cp config.json.template config.json
```

Edit `cli/config.json` and fill in your SDM project ID, Google Cloud OAuth client ID and secret, database credentials, and timezone.

### 4. Set Up Python Environment

```bash
cd cli
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 5. Authorize with Google

```bash
cli/venv/bin/python3 cli/sdm_auth.py
```

This opens a browser for Google OAuth consent, in order to authorize your Google Home account to share device data with your project. After authorizing, the refresh token is saved to `cli/config.json` automatically.

### 6. Verify Connectivity

```bash
cli/venv/bin/python3 cli/sdm_device_info.py
```

You should see your thermostat listed with its current temperature, humidity, and HVAC status.

### 7. Set Up the Database

Choose a password for your local MySQL nest database and update it in `cli/config.json` and `dbsetup`. Then create the database:

```bash
mysql -u root < cli/dbsetup
```

### 8. Set Up Cron Jobs

Create a cron job to collect data every 5 minutes:

```bash
*/5 * * * *     /var/www/html/nestgraph/cli/venv/bin/python3 /var/www/html/nestgraph/cli/sdm_collect.py >> /var/log/nestgraph.log 2>&1
```

Optionally, create a cron job to check if your thermostat has gone offline. Populate the recipient email(s) in `cli/check_nest.sh` if you want email notifications:

```bash
*/30 * * * *    /var/www/html/nestgraph/cli/check_nest.sh
```

### 9. View the Graph

Point your web browser to the `nestgraph` directory on your webserver!


## Troubleshooting

### Refresh token expired or revoked (`invalid_grant`)

If data collection stops with an `invalid_grant` error, the refresh token stored in `cli/config.json` is no longer valid. Common causes:

- **OAuth app left in Testing mode** — Google limits refresh tokens to 7 days for apps in Testing status. Fix: go to Google Cloud Console → APIs & Services → OAuth consent screen → *Publishing status* → **Publish App**, then re-run `sdm_auth.py`.
- **Token inactive for 6 months** — Google revokes tokens unused for 6 months. This won't happen if the cron job runs continuously.
- **Google account password changed** — changing your password revokes all OAuth tokens.
- **Access manually revoked** — if you removed the app at [myaccount.google.com/permissions](https://myaccount.google.com/permissions).

In all cases, re-authorize to get a new refresh token:

```bash
cli/venv/bin/python3 cli/sdm_auth.py
```

## Known Issues

* Only checks for heating on/off, not cooling (I don't have cooling)
* Only supports a single Nest thermostat (I only have one)
* Assumes you want temperatures displayed in Fahrenheit
* Doesn't automatically redraw when you resize the browser window


