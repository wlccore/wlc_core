<?php
namespace eGamings\WLC\LiveChats;

use eGamings\WLC\RestApi\ApiException;

class JivoProvider
    extends AbstractProvider {

    function __construct($liveChatType) {
        parent::__construct($liveChatType);
    }

    //Method is not supported
    public function getDataApi(Array $params)
    {
        throw new ApiException(_('Method is not supported'), 404);
    }

    //Method is not supported
    public function SendRequest(Array $params)
    {
        return [];
    }

    //Method is not supported
    public function syncChatByCron()
    {
        return false;
    }

}