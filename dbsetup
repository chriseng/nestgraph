CREATE DATABASE nest;
GRANT ALL PRIVILEGES ON nest.* TO 'nest_admin'@'localhost' IDENTIFIED BY 'choose_a_db_password';
FLUSH PRIVILEGES;

USE nest;
CREATE TABLE `nest`.`devices` (
   `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
   `serial` varchar(16) NOT NULL,
   `name` varchar(256),
   PRIMARY KEY (`id`),
   UNIQUE KEY (`serial`)
)ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `nest`.`data` (
  `device_id` tinyint unsigned NOT NULL,
  `timestamp` timestamp NOT NULL,
  `heating` tinyint unsigned NOT NULL,
  `cooling` tinyint unsigned NOT NULL,
  `fan` tinyint unsigned NOT NULL,
  `autoAway` tinyint signed NOT NULL,
  `manualAway` tinyint unsigned NOT NULL,
  `leaf` tinyint unsigned NOT NULL,
  `target` numeric(7,3) NOT NULL,
  `target2` numeric(7,3),
  `current` numeric(7,3) NOT NULL,
  `humidity` tinyint unsigned NOT NULL,
  `updated` timestamp NOT NULL,
  `outsideTemperature` numeric(7,3) NOT NULL,
  `outsideHumidity` numeric(7,3) NOT NULL,
  `outsidePressure` numeric(7,3) NOT NULL,
  PRIMARY KEY (`device_id`,`timestamp`) USING BTREE
)ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `nest`.`cycles_data` (
  `device_id` tinyint unsigned NOT NULL,
  `cycleNum` int(10) unsigned NOT NULL,
  `cycleDate` datetime NOT NULL,
  `start` int(10) unsigned NOT NULL,
  `duration` int(10) unsigned NOT NULL,
  `type` int(10) unsigned NOT NULL,
  PRIMARY KEY (`device_id`,`cycleNum`,`cycleDate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `nest`.`energy_data` (
  `device_id` tinyint unsigned NOT NULL,
  `energyDate` datetime NOT NULL,
  `device_timezone_offset` int(11) NOT NULL,
  `total_heating_time` int(10) unsigned NOT NULL,
  `total_cooling_time` int(10) unsigned NOT NULL,
  `total_fan_cooling_time` int(10) unsigned NOT NULL,
  `total_humidifier_time` int(10) unsigned NOT NULL,
  `total_dehumidifier_time` int(10) unsigned NOT NULL,
  `leafs` int(11) NOT NULL,
  `whodunit` int(11) NOT NULL,
  `recent_avg_used` int(10) unsigned NOT NULL,
  `usage_over_avg` int(10) NOT NULL,
  `daily_temp_avg` numeric(7,3),
  `daily_temp_min` numeric(7,3),
  `daily_temp_max` numeric(7,3),
  PRIMARY KEY (`device_id`,`energyDate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `nest`.`events_data` (
  `device_id` tinyint unsigned NOT NULL,
  `eventNum` int(10) unsigned NOT NULL,
  `eventDate` datetime NOT NULL,
  `start` int(10) unsigned NOT NULL,
  `end` int(10) unsigned NOT NULL,
  `type` int(10) unsigned NOT NULL,
  `touched_by` int(10) unsigned NOT NULL,
  `touched_when` int(10) unsigned NOT NULL,
  `touched_timezone_offset` int(11) NOT NULL,
  `touched_where` int(11) NOT NULL,
  `heat_temp` int(11) NOT NULL,
  `cool_temp` int(11) NOT NULL,
  `continuation` int(11) NOT NULL,
  `event_touched_by` int(11) NOT NULL,
  PRIMARY KEY (`device_id`,`eventNum`,`eventDate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
