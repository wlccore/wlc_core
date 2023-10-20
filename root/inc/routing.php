<?php
/**
 * Core routing
 * direct url
 * context contains data array
 * TODO url key's may contain regexp
 * TODO context may be array of data or function
 */

global $_routes, $_context, $_access;


/**
 * 404 for flog
 */

if (strpos($_SERVER['REQUEST_URI'], '/flog') === 0) {
    http_response_code(404);
    exit;
}

/*
 * Including user defined routes
 * This one is primary
*/
if (file_exists(_cfg('root') . '/routing.php')) {
    require_once(_cfg('root') . '/routing.php');
}

if (!isset($_routes)) $_routes = array();

/*
 * Core routes
 * This one is secondary, matching routes will be redeclaretad with user
 */
$_routes = array_merge(
    array(
        /*
         * url_key => array(template[,context])
         */

    	'caching' => 'caching',
    	'fflt' => array('fflt', 'fflt'),
    	'api/v1' => array('', 'eGamings\WLC\RestApi\ApiEndpoints::apiEndpoint'),
    	'api/v2' => array('', 'eGamings\WLC\RestApi\ApiEndpoints::apiEndpoint'),
    ),
    $_routes
);

if ((!isset($_access) || !$_access) && !is_array($_access)) {
    $_access = array();
}
