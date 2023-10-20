<?php
namespace eGamings\WLC;

class Banners extends System {

    public static $_bannersListFile = 'banners.json';
    public static $_bannersV2ListFile = 'bannersV2.json';

    /**
     * get banners with type 'json' and save it to file
     *
     * @param string $version
     * @return array json
     * @throws \Exception
     */
    static function fetchBanners(string $version = 'v1'): array
    {
        $file = '';

        switch ($version) {
            case 'v1':
                $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$_bannersListFile;
                break;
            case 'v2':
                $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$_bannersV2ListFile;
                break;
        }

        $system = System::getInstance();
        $url = '/Banners/List';

        $transactionId = $system->getApiTID($url);

        $hash = md5('Banners/List/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = array(
            'TID' => $transactionId,
            'Hash' => $hash,
            'Type' => 'html',
            'Version' => $version,
        );

        $url .= '?&' . http_build_query($params);
        $response = $system->runFundistAPI($url);
        $result = explode(',', $response, 2);

        if (count($result) != 2 || $result[0] != '1') {
            Logger::log("Unable fetch banner list: " . $response);
            return [];
        } else {
            $banners = json_decode($result[1], true);
            if (!is_array($banners) || count($banners) == 0) {
                Logger::log("Unable fetch banner list: " . $response);
                return [];
            }
        }

        Cache::dropCacheKeys("apiFundistBanners");

        Utils::atomicFileReplace($file, json_encode($banners));

        return $banners;
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    static function getBannersList() {
        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$_bannersListFile;
        if (!file_exists($file) && !self::fetchBanners()) {
            return [];
        }

        $bannersByLang = json_decode(@file_get_contents($file), true);

        if (!empty($bannersByLang)) {
            $isMobile = _cfg('mobileDetected');
            $newBanners = [];

            foreach ($bannersByLang as $lang => $banners) {
                $newBanners[$lang] = [];

                foreach ($banners as $banner) {
                    $allowed = false;

                    if ($isMobile && in_array('mobile', $banner['platform'])) {
                        $allowed = true;
                    }
                    elseif (!$isMobile && in_array('desktop', $banner['platform'])) {
                        $allowed = true;
                    }
                    elseif (in_array('any', $banner['platform'])) {
                        $allowed = true;
                    }

                    if ($allowed) {
                        $newBanners[$lang][] = $banner;
                    }
                }
            }

            $bannersByLang = $newBanners;
        }

        return $bannersByLang;
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public static function getBannersListV2(): array
    {
        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$_bannersV2ListFile;
        if (!file_exists($file) && !self::fetchBanners('v2')) {
            return [];
        }

        $banners = json_decode(@file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        foreach ($banners as &$banner) {
            unset($banner['apiId'], $banner['isActive'], $banner['createdBy']);
        }

        return $banners;
    }
}
