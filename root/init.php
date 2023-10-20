<?php
use eGamings\WLC\Core;
use eGamings\WLC\Logger;

//core version
require_once __DIR__ . '/version.php';

//core config
require_once __DIR__ . '/inc/config.php';

//Support functions
require_once __DIR__ . '/inc/functions.php';

// Init logger
Logger::init();

// Init core
(Core::getInstance())->initCore();

//routing config
require_once __DIR__ . '/inc/routing.php';
