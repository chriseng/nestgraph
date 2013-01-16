# nestgraph

Create pretty charts of your Nest thermostat data.

## Background

The point of this project was to see how well the Nest algorithms work. In particuar, the Nest claims to minimize overshoot, which is a common problem with cast-iron radiators. It also claims to know when to start heating in order to hit your target temperature exactly at the time you scheduled it.  

Unfortunately, you can't actually access historical temperature data on the Nest website or via the iOS app. It shows you when heating was turned on/off and what the temperature targets were at those times, but it doesn't give you any indication of how well or how poorly the thermostat performed. This could be by design, as it's a lot of information to store.  

This project uses an unofficial Nest API to pull your temperature readings periodically and store them in a database so that you can inspect the data yourself in an easily consumable form.

I also wanted an excuse to play with the [D3](http://d3js.org) (Data-Driven Documents) library a little.

## Features

* Polls Nest website to collect thermostat telemetry
* Stores selected data in local MySQL database
* Generates a nice visualization of actual temp vs. set point
* Lower mini-chart is interactive pan-and-zoom of the upper chart
* Hover over the gray circles to get the exact timestamp and temperature

![nestgraph screenshot](https://github.com/chriseng/nestgraph/raw/master/nestgraph-screenshot.png)

## Dependencies

* LAMP stack
* Unofficial [nest-api](https://github.com/gboudreau/nest-api) library by Guillaume Boudreau

## Getting Started

Clone this repo into your web root.

```bash
cd [your-web-root]
git clone https://github.com/chriseng/nestgraph.git
```

Grab a copy of nest-api and unzip into the ```nestgraph``` directory you created in the previous step. It should create a subdirectory called ```nest-api-master```.

```bash
cd nestgraph
wget https://github.com/gboudreau/nest-api/archive/master.zip
unzip master.zip
rm -f master.zip
```
Open ```inc/config.php``` in a text editor and update the ```nest_user``` and ```nest_pass``` variables with your username and password for nest.com.  Update the ```local_tz``` variable to reflect your time zone.

Run the test script to make sure that the API is able to pull your thermostat data correctly from nest.com.

```bash
php test.php
```

If this works, you should see a bunch of stuff fly across the screen, ending with something like this:

```bash
Heating             : 0
Timestamp           : 2013-01-15 22:10:39
Target temperature  : 67.00
Current temperature : 67.53
Current humidity    : 29
```

Choose a password for your local MySQL nest database, and update it in two places: ```inc/config.php``` (the ```db_pass``` variable) and ```dbsetup```.

As root or using a DBA account, run the commands in dbsetup to create the MySQL database that will be used to store historical data.

```bash
mysql -u root < dbsetup
```

Create a cron job to poll the website periodically and update the local database. The thermostat does not phone home on a fixed schedule, but typically it updates in 5 to 30 minute intervals. The script will only insert into the database if there is new data available. Obviously, update the path to ```insert.php``` if it's not in ```/var/www/html/nestgraph```.

```bash
*/5 * * * *     /bin/rm -f /tmp/nest_php_* ; /usr/bin/php /var/www/html/nestgraph/insert.php > /dev/null
```
(FYI, the reason we remove the files in ```/tmp``` is because it seems the nest-api library attempts to cache authentication info too aggressively, and after a few days it ends up trying to connect to an AWS server that no longer exists.)

Point web browser to the ```nestgraph``` directory on your webserver!  Admire pretty graphs (actually, they won't be all that pretty until it has collected some data).


## Known Issues

* Only checks for heating on/off, not cooling (I don't have cooling)
* Only supports a single Nest thermostat (I only have one)
* Heating on/off trendline lazily mapped on to the temperature graph
* Assumes you want temperatures displayed in Fahrenheit
* Doesn't automatically redraw when you resize the browser window
* Labels (current/target/heating) don't follow the trend lines when you pan/zoom
* Need to figure out what's actually wrong with the authentication caching in ```nest-api``` instead of just purging its files in ```/tmp```

