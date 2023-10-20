<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Converters\PdfConverter;

/**
 * @SWG\Tag(
 *     name="PdfConverter",
 *     description="Pdf Converter"
 * )
 */

/**
 * @class WpToPdfResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class WpToPdfResource extends AbstractResource
{

    public function __construct()
    {
        $this->URL = KEEPALIVE_PROXY . '/self_' . $_SERVER['SERVER_NAME'];
    }

    /**
     * @SWG\Get(
     *     path="/wptopdf",
     *     description="Convert WordPress page to pdf",
     *     tags={"pdf"},
     *     @SWG\Response(
     *         response="200",
     *         description="PDF document as string",
     *         @SWG\Schema(
     *             type="string",
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @throws ApiException
     */
    public function get(?array $request, array $query, array $params = [])
    {

        if (empty(_cfg('termsOfService'))) {
            throw new ApiException('', 400, null, [_('No version')]);
        }

        if (
            empty($request['termsOfService'])
            || $request['termsOfService'] !== _cfg('termsOfService')
            ) {
            throw new ApiException('', 400, null, [_('Wrong terms of service version')]);
        }

        $config = [
            'useWpPlugin' => !empty($request['wpPlugin']) ? (bool) $request['wpPlugin'] : false,
            'lang'        => !empty($request['lang']) ? $request['lang'] : '',
            'mode'        => !empty($request['mode']) ? $request['mode'] : '',

            'pageType'    => !empty($request['pageType']) ? $request['pageType'] : '',
            'slug'        => !empty($request['slug']) ? $request['slug'] : '',
            'debug'       => !empty($request['debug']) ? $request['debug'] : '',
            'termsOfService' => $request['termsOfService'],
        ];

        $url = $this->buildUrl($config);
        if (!$url) {
            throw new ApiException('', 400, null, _('Error in generating URL'));
        }

        $result = json_decode($this->curlRequest($url), true);

        // Try loading English version
        if ($config['lang'] !== 'en' && !$result) {
            $config['lang'] = 'en';
            $url = $this->buildUrl($config);
            $result = json_decode($this->curlRequest($url), true);
        }

        if (!$result) {
            if ($config['debug']) {
                return [
                    'version' => _cfg('termsOfService'),
                    'error' => 'curl request failed on requesting the url',
                    'url' => $url,
                    'result (from the curl request)' => $result,
                ];
            }

            throw new ApiException('', 400, null, [_('Error in parsing the text from WordPress')]);
        }

        $versionTemplate = '<p style="text-align: left;">' . _cfg('termsOfService') . '</p>';
        $text = 'Empty page';
        if (is_array($result) && $result[0]['content']['rendered']) {
            $text = $versionTemplate . $result[0]['content']['rendered'];
        }


        if ($config['debug']) {
            $result = [
                'version' => $versionTemplate,
                'result' => $result,
                'url' => $url,
                'text to be rendered in pdf' => $text,
            ];
        } else {
            $pdfFile = PdfConverter::toPdf($text);
            $result = $pdfFile;
        }

        return $result;
    }

    private function curlRequest(string $url): string {
        $ch = curl_init();

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
        ];

        curl_setopt_array($ch, $curlOptions);

        $result = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code === 401 || $http_code === 400) {
            throw new ApiException('', 400, null, _('Error in receiving the text from WordPress'));
        }

        return $result ?: '';
    }

    private function buildUrl(array $config): string {

        $url_part = '';
        $lang_query = '';
        $slug = '&slug=' . ($config['slug'] ?: 'terms-and-conditions');

        if ($config['lang']) {
            $lang_query = '&lang='.$config['lang'];
        }

        $pageType = '';
        switch($config['pageType']) {
            case 'page':
                $pageType = 'pages';
                break;
            case 'post':
                $pageType = 'posts';
                break;
            default:
                $pageType = 'pages';
                break;
        }

        $langPath = '';
        switch($config['mode']) {
            case 'prepath':
                $langPath = $config['lang'];
                break;
            case 'query':
            default:
                $langPath = '';
                break;
        }

        // Generate WordPress part
        if ($config['useWpPlugin']) {
            $url_wp_part = "/content/${langPath}/wp-json/wp-wlc-api/v1";
        } else {
            $url_wp_part = "/content/{$langPath}/wp-json/wp/v2";
        }

        // Build the url from the parts
        $query = '?_embed=1&_fields=content&context=view&parent=0';
        $query .= $slug;
        $generated_url = $this->URL . $url_part . $url_wp_part . '/' . $pageType . $query . $lang_query;

        return $generated_url;
    }
}
