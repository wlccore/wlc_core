<?php
namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiException;

class Documents extends System
{
    public $user;

    private static $ADDITIONAL_HASH_FIELDS = ['DocumentID'];

    public function __construct()
    {
        $this->user = new User();
    }

    /**
     * Get documents list by user
     *
     * @public
     * @method GetList
     *
     * @return {array}
     * @throws {\Exception}
     */
    public function GetList()
    {
        $response = $this->SendRequest('GetList');
        $data = explode(',', $response, 2);

        if ($data[0] == 1) {
            $arr = json_decode($data[1], true);

            foreach ($arr as &$a) {
                $a['FileName'] = implode('.', [!empty($a['FileName']) ? $a['FileName'] : 'tmp', $a['FileType']]);
                $a['Link'] = '/api/v1/docs/' . $a['ID'] . '/' . urlencode($a['FileName']);
                $a['DownloadLink'] = $a['Link'] . '?download=y';
            }
            unset($a);
        } else {
            throw new \Exception(!empty($data[1]) ? $data[1] : _('Unknown error'), 400);
        }

        return $arr;
    }

    // @codeCoverageIgnoreStart
    public function GetExtensionsList()
    {
        $response = $this->SendRequest('Extensions');
        $data = explode(',', $response, 2);

        if ($data[0] == '1') {
            $extensions = json_decode($data[1], true);
            if (in_array('jpg', $extensions, true)) {
                $extensions[] = 'jpeg';
            }
            return $extensions;
        } else {
            throw new \Exception(!empty($data[1]) ? $data[1] : _('Unknown error'), 400);
        }
    }
    // @codeCoverageIgnoreEnd

    public function getById($id) {
        $docs = $this->getList();

        foreach ($docs as $doc) {
            if ($doc['ID'] == $id) {
                return $doc;
            }
        }

        return null;
    }

    /**
     * Get documents types
     *
     * @public
     * @method getTypes
     *
     * @return {array}
     */
    public function getTypes($lang = 'en')
    {
        $rsp = $this->SendRequest('GetTypes', ['language' => $lang]);
        $types = $this->makeResponse($rsp);
        
        $typeKeys = [];

        foreach ($types as $type) {
            $typeKeys[] = $type['TypeKey'];
        }

        $descriptions = $this->SendRequestDescription('GetDocumentDescriptionByTypeKeys', ['typeKey' => implode(',', $typeKeys)]); 
        $descriptions = json_decode($descriptions, true);

        if ((int)$descriptions['code'] === 200) {
            $types = $this->addDescriptionToType($types, $descriptions, $lang);
        }

        return $types;
    }

    /**
     * @param mixed $types
     * @param mixed $descriptions
     */
    private function addDescriptionToType($types, $descriptions, $lang) 
    {
        foreach ($types as &$item1) {
            foreach ($descriptions['data'] as $item2) {
                if ((int)$item1['PID'] === (int)$item2['docTypeId']) {
                    if (empty($item2['shortDescription'][$lang])) {
                        $lang = 'en';
                    }

                    $item1['ShortDescription'] = $item2['shortDescription'][$lang];
                    $item1['FullDescription'] = $item2['fullDescription'][$lang];

                    break;
                }
            }
        }

        return $types;
    }

    /**
     * Get documents types
     *
     * @public
     * @method getTypes
     *
     * @return {array}
     */
    public function getDocumentsByMode($mode = 'manual', $lang = 'en')
    {
        $rsp = $this->SendRequest('GetDocumentsByMode', [
            'mode' => $mode,
            'language' => $lang
        ]);

        return $this->makeResponse($rsp);
    }
    /**
     * Download document by ID
     *
     * @public
     * @method Download
     * @param {int} $id
     *
     * @return {array}
     */
    public function Download($id)
    {
        $rsp = $this->SendRequest('Download', ['DocumentID' => (int)$id]);
        return $this->makeResponse($rsp);
    }

    /**
     * Upload documents
     *
     * @public
     * @method Upload
     * @param {array} $files
     * @param {int} $documentsType
     * @param {string} [$Description='']
     *
     * @return {array}
     */
    public function Upload($files, $documentsType, $Description = '', $isSystemUpload = false)
    {
        $rsp = $this->SendRequest('Upload', [
            'Files' => json_encode($files, JSON_UNESCAPED_UNICODE),
            'DocType' => $documentsType,
            'Description' => $Description,
            'isSystemUpload' => $isSystemUpload,
        ], true);

        return $this->makeResponse($rsp, false);
    }

    /**
     * Delete document by ID
     *
     * @public
     * @method DeleteById
     * @param {int} $id
     * @param {string} [$comment=null]
     *
     * @return array
     */
    public function DeleteById($id, $comment = null)
    {
        $reqParams = ['DocumentID' => (int)$id];

        if (isset($comment)) {
            $reqParams['StatusDescription'] = $comment;
        }

        $rsp = $this->SendRequest('Delete', $reqParams);
        return $this->makeResponse($rsp);
    }

    /**
     * Generate response
     *
     * @private
     * @method makeResponse
     * @param {string} $rsp
     * @param {boolean} [$isArraySuccess=true]
     *
     * @return {array|string}
     */
    private function makeResponse($rsp, $isArraySuccess = true)
    {
        $data = explode(',', $rsp, 2);

        if ($data[0] == 1) {
            $arr = $isArraySuccess ? json_decode($data[1], $isArraySuccess) : $data[1];
        } else {
            $arr = [
                'code' => (int)$data[0],
                'error' => !empty($data[1]) ? $data[1] : _('Unknown error')
            ];
        }

        return $arr;
    }

    /**
     * Send GET request
     *
     * @private
     * @method SendRequest
     * @param {string} $path
     * @param {array} $params
     *
     * @return string
     */
    private function SendRequest($path, $params = [], $post = false)
    {
        $login = Front::User('id');

        if (!$login) {
            return null;
        }

        $url = '/Documents/' . $path . '/?&Login=' . (int)$login;
        $transactionId = $this->getApiTID($url);

        $dataHash = 'Documents/' . $path . '/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass');

        foreach ($params as $param => $value) {
            if (in_array($param, self::$ADDITIONAL_HASH_FIELDS)) {
                $dataHash .= ('/' . $value);
            }
        }

        $hash = md5($dataHash);

        $params = array_merge($params, [
            'Password' => $this->user->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        ]);

        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url, 0, '', $post);
    }

    /**
     * @param mixed $path
     * @param mixed $params
     * @param bool $post
     */
    private function SendRequestDescription($path, $params = [], $post = false)
    {
        $login = Front::User('id');

        if (!$login) {
            return null;
        }

        $url = '/DocumentDescriptionRouter/' . $path . '/?&Login=' . (int)$login;
        $transactionId = $this->getApiTID($url);

        $dataHash = 'DocumentDescriptionRouter/' . $path . '/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass');

        $hash = md5($dataHash);

        $params = array_merge($params, [
            'Password' => $this->user->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        ]);

        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url, 0, '', $post);
    }

}
