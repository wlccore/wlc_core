<?php

use eGamings\WLC\Affiliate;
use eGamings\WLC\Db;
use eGamings\WLC\System;
use eGamings\WLC\Classifier;


function go($link = '')
{
    if (!$link) {
        $link = _cfg('site');
    }

    echo '<script>window.location.replace("' . $link . '");</script>';
    exit();
}

function _cfg($key)
{
    global $cfg;

    $fnArgs = func_get_args();

    if (sizeof($fnArgs) > 1) {
        return $cfg[$key] = $fnArgs[1];
    }

    if (!isset($cfg[$key])) {
        return '';
    }

    return $cfg[$key];
}

function dump($var)
{
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

function ddump($var)
{
    dump($var);
    exit();
}

function isCloudflareIP($ip){

    $ranges = Classifier::getCloudflareIPList();
    if($ranges === false)
        return false;
    $ip = ip2long($ip);
    foreach($ranges as $range){
        if($ip>=$range['from'] && $ip<=$range['to'])
            return true;
    }
    return false;
}

function fflt()
{

    $rv = array();
    $route = explode('/', $_GET['route']);

    switch ($route[1]) {
        case '1': //EGASS

            // number of days to store cookie
            $cookie_days_period = 30;

            // max size of parameter to store in cookie (bytes)
            $max_cookie_size = 128;

            $values = Array();
            foreach (Array('clickid', 'affid') as $field) {
                if (isset($_GET[$field]) && strlen($_GET[$field]) > 0 && strlen($_GET[$field]) < $max_cookie_size) {
                    $values[] = $_GET[$field];
                }
            }

            if (count($values) > 0) {
                setcookie("egass", implode(':', $values), time() + 60 * 60 * 24 * $cookie_days_period, "/");
            }

            if (isset($_GET['affid']) && strlen($_GET["affid"]) > 0 && strlen($_GET["affid"]) < $max_cookie_size) {
                setcookie("affid", $_GET["affid"], time() + 60 * 60 * 24 * $cookie_days_period, "/");
            }

            if (isset($_GET['clickid']) && strlen($_GET["clickid"]) > 0 && strlen($_GET["clickid"]) < $max_cookie_size) {
                setcookie("clickid", $_GET["clickid"], time() + 60 * 60 * 24 * $cookie_days_period, "/");
            }

            //---
            setcookie("sitelang", '', time() - 24 * 3600, "/");

            $url = "/";
            if (isset($_GET['url']) && strlen($_GET["url"])) {
                $url = urldecode($_GET["url"]);
            }

            if (!strpos($url, '://')) {
                if (substr($url, 0, 1) == "/")
                    $url = '//' . $_SERVER['HTTP_HOST'] . $url;
                else {
                    $dir = dirname($_SERVER["PHP_SELF"]);
                    if ($dir == '/' || $dir == "\\")
                        $dir = "";
                    $url = '//' . $_SERVER['HTTP_HOST'] . $dir . "/" . $url;
                }
            }

            header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');

            $rv['url'] = $url;
            break;

        //Globo-Tech
        case '2':
            // check && save partner_id to cookies
            //--
            // number of days to store cookie
            $cookie_days_period = 30;

            // max size of parameter to store in cookie (bytes)
            $max_cookie_size = 256;

            $values = Array();
            foreach (Array('partner_id', 'subid') as $field) {
                if (isset($_GET[$field]) && strlen($_GET[$field]) > 0 && strlen($_GET[$field]) < $max_cookie_size) {
                    $values[] = $_GET[$field];
                }
            }

            $affiliate_id = '';
            if (count($values) > 0) {
                //unreal bullshit, requestsed by Globo-Tech
                $affiliate_id = implode('&subid=', $values);
                setcookie("globo-tech", $affiliate_id, time() + 60 * 60 * 24 * $cookie_days_period, "/");
            }

            //-- save hit with partner_id
            Db::connect();

            $ip = System::getUserIP();
            $result = Db::query('INSERT INTO affiliate_hits (ip, affiliate_id, affiliate_system, add_date) VALUES("' . Db::escape($ip) . '", "' . Db::escape($affiliate_id) . '", "globo-tech", NOW())');

            //-- redirect to frontpage
            $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/';
            header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');

            $rv['url'] = $url;
            break;

        default:
            $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/';
            $rv['url'] = $url;
            break;
    }

    if (isset($_GET['redir_cookie']) && Affiliate::getSystem() != '') {
        $currentUrl = 'http' . (empty($_SERVER['HTTPS']) ? '' : 's') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $_GET['redir_cookie'] = substr($_GET['redir_cookie'], 0, 64);
        setcookie($_GET['redir_cookie'], $currentUrl, time() + ((60 * 60 * 24) * Affiliate::getCookieDaysPeriod()), "/");
    }

    return $rv;
}
