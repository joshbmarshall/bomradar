# BOMRadar

This package fetches images from the public ftp server of the Australian Bureau of Meteorology for their RADAR service for repackaging onto your own website.

## Installation

Installation is very easy with composer:

	composer require cognito/bomradar

If you don't have composer, download the Radar.php file and include it into your project.

## Setup

Setup is simple, go to the Radar site http://www.bom.gov.au/australia/radar/ and choose a radar to fetch.
Next get the radar size, e.g. 64, 128 or 256km radius and take note of the URL.

For example the 128km Brisbane Loop is IDR663.

We use just the number part, so in this case 663.

## Cron to get the images

Set up a regular call through a cron or similar every 10 minutes, to sync and clean the local files.

	<?php
	// Call this every 10 minutes through a cron or similar
	$radar = new \Cognito\BOMRadar\Radar('663');
	$radar->sync(__DIR__ . '/assets', 2);

The sync() command takes the full path to the folder to store the files, and the number of hours to keep the files there for.
Set the number of hours to 0 to keep them forever.

If you make the number of hours 1, you will continually download some files as the BOM seems to keep files on their ftp for just under two hours.

## Output

To output the radar to the browser, you can use the built-in renderer or make up your own.

	<?php
	// Call this to render the radar to the browser
	$radar = new \Cognito\BOMRadar\Radar('663');
	echo $radar->render(__DIR__ . '/assets', '/assets', 6);