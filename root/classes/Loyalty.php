<?php
namespace eGamings\WLC;

class Loyalty {
    private static $instance = null;

    public static function getInstance() {
        if (!is_object(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function send($url, $params = []) {
        $ch = curl_init();

        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query($params),
        );

        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

	public static function Request($path, $params, $returnArray = true)
	{
	    $params['TID'] = 'FUNDIST';

		//workaround to avoid http_build_query insert 0 instead of false
		foreach ($params as $k=>&$v) {
		    if ($v === false) {
		        $v = 0;
		    }
		}

        $hash = self::prepareHashParams($params);
        $params['Hash'] = md5(_cfg('fundistApiKey') . '/' . $path . '/' . $hash . '/' . _cfg('fundistApiPass'));
        $url = _cfg('loyaltyUrl') . '/' . _cfg('fundistApiKey') . '/' . $path . '/';

		$response = self::getInstance()->send($url, $params);

		$result = json_decode($response, true);
		if (is_array($result)) {
		    if (isset($result['error'])) {
		        throw new \Exception(dgettext('loyalty', $result['error']), 400);
			}
		} else {
			throw new \Exception(_('Loyalty result not supported'), 503);
		}

		return ($returnArray) ? $result : $response;
	}

	public static function prepareHashParams($p, $key = '')
	{
	    if (!is_array($p)) {
	        return '';
	    }

		ksort($p);
		$hash = [];

		foreach ($p as $k => $v) {
			if (is_array($v)) {
				if (!empty($v)) {
				    $hash[] = self::prepareHashParams($v, $k);
				}
				continue;
			}

			if ($key !== '' && $key !== 0) {
				$k = $key . '[' . $k . ']';
			}

			if ((string) $v !== '') {
				$hash[] = $k . '=' . (string) $v;
			}
		}

		return implode('/', $hash);
	}
}

