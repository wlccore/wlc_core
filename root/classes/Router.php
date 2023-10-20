<?php
/**
 *
 */
namespace eGamings\WLC;

class Router {
    private $routes;
    private $access;
    private $route;
    private $hooked_routes;
    static private $r = false;

    function __construct()
    {
        global $_routes, $_access;

        $this->routes = (is_array($_routes)) ? $_routes : [];
        $this->hooked_routes = [];
        $this->access = $_access;
        $this->route = isset($_GET['route']) ? $_GET['route'] : '';

        $hooks = _cfg('hooks');
        if (is_array($hooks)) foreach ($hooks as $hook => $functions) {
            if (preg_match('/^route:(.+)$/', $hook, $matches)) {
                $this->hooked_routes[] = $matches[1];
            }
        }

        if (_cfg('enableCors')) {
            $corsHost = '*';
            if (!empty($_SERVER['HTTP_ORIGIN'])) {
                $corsOrigin = parse_url($_SERVER['HTTP_ORIGIN']);
                if (!empty($corsOrigin['host'])) {
                    $corsHost = (!empty($corsOrigin['scheme']) ? $corsOrigin['scheme'] : 'http') . '://' . $corsOrigin['host'];
                    if (!empty($corsOrigin['port'])) {
                        $corsHost .= ':' . $corsOrigin['port'];
                    }
                }
            }
            header('Access-Control-Allow-Origin: ' . $corsHost);
            header('Access-Control-Allow-Credentials: true');
        }
    }

    public static function getInstance()
    {
        if (!self::$r) self::$r = new self();
        return self::$r;
    }

    public static function getRoute()
    {
        $r = self::getInstance();
        return $r->route;
    }

    private static function getTplPostfix($tpl) {
        if ($tpl == 'index' && _cfg('mobile')) {
            return $tpl.'_m';
        }

        return $tpl;
    }

    public static function getPage($route = false)
    {
        $r = self::getInstance();

        if ($route === false) $route = $r->route;

        $r->checkRedirect($route);

        $region = System::getGeoCityData();
        if (_cfg('enableForbidden') !== false 
             && ((isset($region) && System::isCountryRegionForbidden($region))
                || System::isCountryForbidden(_cfg('userCountry'), System::getUserIP()))
        ) {
            $siteconfigFilePath = _cfg('root') . DIRECTORY_SEPARATOR . 'siteconfig.json';
            $context = $r->getContext();
            $context['useCustomLogo'] = '';
            $context['userIP'] = System::getUserIP();
            $context['userCountry'] = _cfg('userCountry') . '/' . $region;

            if (file_exists($siteconfigFilePath)) {
                $config = json_decode(file_get_contents($siteconfigFilePath), true);

                if (json_last_error() === JSON_ERROR_NONE && $config['siteconfig']['useCustomLogo']) {
                    $context['useCustomLogo'] = $config['siteconfig']['useCustomLogo'];
                }
            }

            http_response_code(451);
            return [
                'tpl' => 'forbidden',
                'context' => $context
            ];
        }

        foreach ($r->routes as $k => $v) {
            if ($k == $route) {
                if (is_string($v)) $v = array($v);

                $context = $r->getContext(isset($v[1]) ? $v[1] : '');
                if (is_array($context)) {
                    if (in_array($route, $r->hooked_routes)) {
                        $context = array_merge($context, System::hook('route:'.$route));
                    }
                }

                return array(
                    'tpl' => self::getTplPostfix($v[0]),
                    'context' => $context
                );
            }
        }

        $route = explode('/', $route);
        array_pop($route);
        if (count($route) > 0) {
            return self::getPage(implode('/', $route));
        }

        return array(
            'tpl' => self::getTplPostfix('index'),
            'context' => $r->getContext(array('route' => $r->route)),
        );
    }

    function checkRedirect($route)
    {
        $User = intval(Front::User('id') > 0);

        if ($User) {
        	$check = !empty($this->access['user']) ? $this->access['user'] : [];
        } else {
        	$check = !empty($this->access['guest']) ? $this->access['guest'] : [];
        }

        foreach ($check as $url => $pages) {
            foreach ($pages as $k => $page) {
                if ($route == $page) {
                    header('Location: ' . _cfg('href') . '/' . $url);
                    die();
                }
            }
        }
    }

    private function getContext($params = '')
    {
        global $_context; //user defined global context

        $context = array(
            'GET' => $_GET,
            'POST' => $_POST,
            'env' => _cfg('env'),
            'projectVersion' => _cfg('projectVersion'),
            'language' => _cfg('language'),
            'href' => _cfg('href'),
            'img' => _cfg('img'),
            'js' => _cfg('js'),
            'css' => _cfg('css'),
            'page' => $this->route,
            'core' => array(
                'js' => _cfg('site') . '/core/static/js',
                'css' => _cfg('site') . '/core/static/css',
            ),
            'authUser' => User::isAuthenticated(),
            'supportEmail' => _cfg('supportEmail'),
            'sentryName' => _cfg('SentryName'),
            'app' => [
                'server' => $_SERVER,
                'request' => $_REQUEST,
                'env' => $_ENV,
            ],
        );

        if (!empty($_context) && is_array($_context)) {
            $context = array_merge($context, $_context);
        }

        if (empty($params)) {
            return $context;
        }

        $params = $this->callParamsFunctions($params);

        if (is_array($params)) {
            $context = array_merge($context, $params);
        } else if (_cfg('env') == 'dev' && $params) {
            die($params);
        }

        return $context;
    }

    function callParamsFunctions($params)
    {
        if (is_string($params)) {
            if ($params == 'fflt') {
                return fflt();
            }

            if (method_exists('eGamings\\WLC\\Front', $params)) {
                return forward_static_call(array('eGamings\\WLC\\Front', $params), null);
            } else {
                $call = explode('::', $params, 2);

                if (isset($call[1]) && is_callable($call[0], $call[1])) {
                    return call_user_func(Array($call[0], $call[1]));
                } else if (method_exists('eGamings\\WLC\\Front', $call[0])) {
                    return forward_static_call(array('eGamings\\WLC\\Front', $call[0]), (isset($call[1]) ? $call[1] : null));
                }
            }
        } else if (is_array($params)) {
            foreach ($params as $k => $v) {
                $params[$k] = $this->callParamsFunctions($v);
            }
        }

        return $params;
    }

    public static function error($error_number)
    {
        if (in_array($error_number, Array('404'))) {
            header(':', true, $error_number);
            $error_file = _cfg('root') . DIRECTORY_SEPARATOR . 'error' . $error_number . '.html';
            if (file_exists($error_file)) {
                $file = file_get_contents($error_file);
                echo $file;
            }
            exit(1);
        }
    }

    public static function redirect($url, $code = '301')
    {
        header('Location: ' . $url, true, $code);
        exit(1);
    }

}
