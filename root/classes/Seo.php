<?php
namespace eGamings\WLC;

class Seo {
    private static $parsedUrl;
    private static $newUrl;
    private static $instance;
    static $_SeoFile = 'seo.json';
    static $_apiEnabled = true;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function wpApiCall($url) {
        return (self::$_apiEnabled) ? @file_get_contents($url) : false;
    }

    public static function fetchSeo()
    {
        $siteConfig = Config::getSiteConfig();
        $langs = (!empty($siteConfig['languages'])) ? $siteConfig['languages'] : [
            "1" => [
                "ID" => "1",
                "Code" => "en",
                "Name" => "English",
                "NameEn" => "English"
            ]
        ];

        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$_SeoFile;

        $wpHost = _cfg('site');
        if (empty($wpHost) && empty($_SERVER['HTTP_HOST'])) {
            return [];
        }

        if (empty($wpHost)) {
            $wpHost = 'https://' . $_SERVER['HTTP_HOST'];
        }
        $wpRestPrefix = $wpHost. '/content//wp-json/wp/v2';
        $wpRestCategories = $wpRestPrefix.'/categories?slug=seo';
        $info = self::getInstance()->wpApiCall($wpRestCategories);
        if ($info == false) {
            return [];
        }

        $info = json_decode($info, true);
        if (empty($info) || empty($info[0])) {
            return [];
        }

        $categoryId = $info[0]['id'];

        $tags = [];

        foreach ($langs as $langConf) {
            $lang = $langConf['Code'];
            $langShort = explode("-", $langConf['Code'])[0];
            $tags[$lang] = [];

            if (_cfg('qtranslateMode') == "pre-path") {
                $wpRestPrefix = $wpHost. '/content/' . $langShort . '/wp-json/wp/v2';
            }

            $seoPostsUrl = $wpRestPrefix.'/posts?categories=' . $categoryId . '&per_page=100&lang=' . $langShort;
            $seoData = self::getInstance()->wpApiCall($seoPostsUrl);
            $seo = json_decode($seoData, true);

            if (empty($seo) || !is_array($seo)) continue;

            foreach ($seo as $post) {
                $seoData = $post['acf'];
                $seoState = $seoData['state'];

                if (empty($seoState)) continue;

                $seoInfo = [
                    'title' => $seoData['opengraph_title'],
                    'description' => $seoData['opengraph_description'],
                    'keywords' => $seoData['opengraph_keywords'],
                    'image' => $seoData['opengraph_image']
                ];

                if (empty($tags[$lang][$seoState]) || empty($seoData['url'])) {
                    $tags[$lang][$seoState] = $seoInfo;
                }

                if (!empty($seoData['url'])) {
                    if (empty($tags[$lang][$seoState]['urls'])) {
                        $tags[$lang][$seoState]['urls'] = [];
                    }
                    $tags[$lang][$seoState]['urls'][$seoData['url']] = $seoInfo;
                }
            }
        }

        self::$_apiEnabled ? Utils::atomicFileReplace($file, json_encode($tags, JSON_UNESCAPED_UNICODE)) : '';

        return $tags;
    }

    public static function getData()
    {
        $file_name = _cfg('cache') . DIRECTORY_SEPARATOR . "/seo.json";

        $data = [];
        if (file_exists($file_name)) {
            $json = json_decode(file_get_contents($file_name), true);
            if (!empty($json)) {
                $data = $json;
            }
        }

        return $data;
    }

    public static function checkRedirects()
    {
        $redirectTo = '';

        self::$newUrl = self::$parsedUrl = parse_url((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . (!empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI']: '/'));
        $host = self::$parsedUrl['host'];

        // Check for redirects
        $domains = (array)_cfg('domains');

        // Any mobile device (phones or tablets).
        if (_cfg('mobile')) {
            $domains = (array)_cfg('mobile_domains');
        }

        if (array_key_exists($host, $domains) && $domains[$host] != $host) {
            $redirectTo = $domains[$host];
            self::$newUrl['host'] = $domains[$host];
        }
        
        // remove / in the end of url
        if ($redirectTo || preg_match('/.+\/$/i', self::$parsedUrl['path'])) {
            header('Location: ' . self::buildUrl(), true, 301);
            die();
        }
    }

    private static function buildUrl() {
        return self::$newUrl['scheme'] . '://' .
               self::$newUrl['host'] .
               (
                   !empty(self::$newUrl['port']) && !in_array(self::$newUrl['port'], [80, 443]) ? 
                   ':' . self::$newUrl['port'] : 
                   ''
                ) .
                (
                    !empty(self::$newUrl['path']) ?
                    rtrim(self::$newUrl['path'], '/') :
                    '/'
                ) . 
                (
                    !empty(self::$newUrl['query']) ? 
                    '?' . self::$newUrl['query'] :
                    ''
                );
    }
}
