<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Documents;

/**
 * @SWG\Tag(
 *     name="docs",
 *     description="Documents"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Document",
 *     description="Document",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="integer",
 *         example=123,
 *         description="Document ID"
 *     ),
 *     @SWG\Property(
 *         property="IDUser",
 *         type="integer",
 *         example=123,
 *         description="ID of user"
 *     ),
 *     @SWG\Property(
 *         property="IDVerifier",
 *         type="integer",
 *         example=123,
 *         description="ID of verifier"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         enum={"Removed", "FailedValidation", "AwaitingValidation", "Validated"},
 *         description="Status of document"
 *     ),
 *     @SWG\Property(
 *         property="StatusDescription",
 *         type="string",
 *         description="Comment of status"
 *     ),
 *     @SWG\Property(
 *         property="AddDate",
 *         type="string",
 *         example="2017-10-10 08:19:39",
 *         description="Date of added document"
 *     ),
 *     @SWG\Property(
 *         property="UpdateDate",
 *         type="string",
 *         example="2017-10-10 08:19:39",
 *         description="Date of verified document"
 *     ),
 *     @SWG\Property(
 *         property="FileType",
 *         type="string",
 *         enum={"png", "jpg", "gif", "pdf"},
 *         description="Type of file"
 *     ),
 *     @SWG\Property(
 *         property="DocType",
 *         type="integer",
 *         enum={1, 2, 3},
 *         description="Type of focument"
 *     ),
 *     @SWG\Property(
 *         property="Description",
 *         type="string",
 *         description="Description of document"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Type",
 *     description="Document type",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="integer",
 *         example=1,
 *         description="Document type ID"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="object",
 *         example={"en": "Passport"},
 *         description="Name of type"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Extension",
 *     description="Document extension",
 *     type="string",
 *     example="pdf",
 * )
 */

/**
 * @class DocumentsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class DocumentsResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/docs",
     *     description="Returns documents list.",
     *     tags={"docs"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Document"
     *             )
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
     * @SWG\Get(
     *     path="/docs/types",
     *     description="Return list of documents types valid in API",
     *     tags={"docs"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Type"
     *             )
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
     * @SWG\Get(
     *     path="/docs/extensions",
     *     description="Return list of documents extensions valid in API",
     *     tags={"docs"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="object",
     *             @SWG\Items(
     *                 ref="#/definitions/Extension"
     *             )
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
     * @SWG\Get(
     *     path="/docs/{id}",
     *     description="Return document",
     *     tags={"docs"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         description="Document id",
     *         required=true
     *     ),
     *      @SWG\Parameter(
     *         name="download",
     *         in="query",
     *         type="string",
     *         description="Downloadable",
     *         example="y",
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Document",
     *         @SWG\Property(
     *             type="object",
     *             @SWG\Items(
     *                 ref="#/definitions/Document"
     *             )
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
     * Get documents list or Download document
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     *
     * @return {array}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException('User is not authorized', 401);
        }

        $type = !empty($params['type']) ? $params['type'] : '';
        $documents = new Documents();

        switch ($type) {
            case 'types':
                $language = !empty($params['lang']) ? $params['lang'] : _cfg('language');
                $res = $documents->getTypes($language);

                if (isset($res['error']) && isset($res['code'])) {
                    throw new ApiException($res['error'], 400, null, [], $res['code']);
                }

                return $res;
                break;
            case 'mode':
                $language = $params['lang'] ?: _cfg('language');
                $mode = $query['mode'] ?: 'manual'; 

                $document = $documents->getDocumentsByMode($mode,$language);

                if (isset($document['error']) && isset($document['code'])) {
                    throw new ApiException($document['error'], 400, null, [], $document['code']);
                }

                return $document;
            case 'extensions':
                return $documents->GetExtensionsList();
            default:
                if (!isset($params['id'])) {
                    return $docs = $documents->GetList();
                } else {
                    $document = $documents->getById($params['id']);
                    $params['file'] = !empty($params['file']) ? urldecode($params['file']) : '';

                    if ($document === null) {
                        throw new ApiException('Document not found', 404);
                    }

                    if (!empty($params['file'])) {
                        if ($params['file'] != $document['FileName']) {
                            throw new ApiException('Document not found', 404);
                        }

                        $res = $documents->Download($params['id']);

                        if (isset($res['error']) && isset($res['code'])) {
                            throw new ApiException($res['error'], 404, null, [], $res['code']);
                        }

                        header('HTTP/1.1 200 OK');
                        header('Expires: 0');
                        header('Cache-Control: no-cache, no-store, must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . $res['ContentLength']);

                        if (!empty($query['download']) && $query['download'] == 'y') {
                            header('Content-Description: File Transfer');
                            header('Content-Disposition: attachment; filename=' . $document['FileName']);
                            header('Content-Transfer-Encoding: binary');
                            header('Content-Type: ' . $res['ContentType']);
                        }
                        elseif (preg_match('/^application\/(?:jpg|png|gif)$/', $res['ContentType'])) {
                            header('Content-Type: ' . str_replace('application', 'image', $res['ContentType']));
                        } else {
                            header('Content-Type: ' . $res['ContentType']);
                        }

                        echo @base64_decode($res['Base64Content']);
                        exit();
                    } else {
                        return $document;
                    }
                }

                break;
        }
    }

    /**
     * @SWG\Post(
     *     path="/docs",
     *     description="Upload new documents",
     *     tags={"docs"},
     *     @SWG\Parameter(
     *         name="file",
     *         in="query",
     *         type="string",
     *         description="Path to tmp file for upload",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="file1",
     *         in="query",
     *         type="string",
     *         description="Path to tmp file for upload",
     *         required=false
     *     ),
     *     @SWG\Parameter(
     *         name="Description",
     *         in="query",
     *         type="string",
     *         description="Description of files"
     *     ),
     *     @SWG\Parameter(
     *         name="DocType",
     *         in="query",
     *         type="integer",
     *         description="Type of document",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success upload"
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
     * Add new document
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} [$params=[]]
     *
     * @return {array}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        if (empty($_FILES) || empty($request['DocType'])) {
            throw new ApiException(_('Uploadable files is empty'), 400);
        }

        $documentsType = $request['DocType'];
        $Description = !empty($request['Description']) ? $request['Description'] : '';

        if (!_cfg('maxFileSize')) {
            $maxFileSize = 4; //max file size (Mb)
        } else {
            $maxFileSize = (int)_cfg('maxFileSize');
        }

        $files = [];

        foreach ($_FILES as $file => $mime_content) {
            if (empty($mime_content['tmp_name'])) {
                continue;
            }

            $fileSize = round($mime_content['size'] / pow(1024, 2), 3);

            if ($fileSize > $maxFileSize) {
                throw new ApiException(_('Exceeds allowed file size') . ' - ' . $maxFileSize . _('Mb'), 400);
            }

            $files[] = [
                'name' => substr($mime_content['name'], 0, strripos($mime_content['name'], '.')),
                'content' => base64_encode(file_get_contents($mime_content['tmp_name']))
            ];
        }

        if (empty($files)) {
            throw new ApiException('Uploadable files is empty', 400);
        }

        $documents = new Documents();
        $res = $documents->Upload($files, $documentsType, $Description);

        if (is_array($res) && isset($res['error']) && isset($res['code'])) {
            throw new ApiException($res['error'], 400, null, [], $res['code']);
        }

        return $res;
    }

    /**
     * @SWG\Delete(
     *     path="/docs/{id}",
     *     description="Delete document",
     *     tags={"docs"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         description="Document id",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="comment",
     *         in="query",
     *         type="string",
     *         description="Comment of deleting"
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 type="string",
     *                 property="ID",
     *                 description="ID of document",
     *                 example="123"
     *             ),
     *             @SWG\Property(
     *                 type="integer",
     *                 property="Status",
     *                 description="Status of document",
     *                 example="-100"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Delete document
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     *
     * @return {array}
     * @throws {ApiException}
     */
    public function delete($request, $query, $params)
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        if (!isset($params['id'])) {
            throw new ApiException(_('Document ID not found'), 404);
        }

        $documents = new Documents();
        $documentID = $params['id'];
        $comment = isset($query['comment']) ? $query['comment'] : null;

        $res = $documents->DeleteById($documentID, $comment);

        if (isset($res['error']) && isset($res['code'])) {
            throw new ApiException($res['error'], 403, null, [], $res['code']);
        }

        return $res;
    }
}
