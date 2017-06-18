<?php

include 'vendor/autoload.php';

// Call this every 10 minutes through a cron or similar
$radar = new \BOMRadar\Radar('663');
$radar->sync(__DIR__ . '/assets');

// Call this to render the radar to the browser
$radar = new \BOMRadar\Radar('663');
echo $radar->render(__DIR__ . '/assets', '/assets', 6);
