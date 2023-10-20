<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Converters\FormToFileConverter;
use eGamings\WLC\Converters\PdfConverter;
use eGamings\WLC\Documents;

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
class KYCResource extends AbstractResource
{
    public function post($request, $query, $params = [])
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $text = $this->generateTextFroRequest($request, $_SESSION['user']['email']);
        $pdfFile = FormToFileConverter::toPdf($text, "KYCQuestionnaire");
        $result = $pdfFile;

        if (!$result) {
            throw new ApiException('', 400, null, ['Error in parsing']);
        }

        $documents = new Documents();
        $files = [];
        $files[] = [
            'name' => 'kycquestionnaire',
            'content' => base64_encode($pdfFile)
        ];

        if (empty($files)) {
            throw new ApiException('Uploadable files is empty', 400);
        }
        $res = $documents->Upload($files, 'KYCQuestionnaire', 'kycquestionnaire', true);

        if (is_array($res) && isset($res['error']) && isset($res['code'])) {
            throw new ApiException($res['error'], 400, null, [], $res['code']);
        }

        return $res;
    }

    private function generateTextFroRequest(array $fields, string $email): string
    {
        $text = "<h2>KYC Questionnaire</h2>";
        $text .= "<h5 style='color: #777'>";
        $text .= $email;
        $text .= "</h5>";
        $text .= "<table style='width: 100%; border: 1px solid #eee; padding: 5px; margin: 0 auto;border-collapse: collapse;'>";
        $text .= "<thead style='border-bottom: 1px solid #eee;'><tr><th colspan='2' style='padding: 10px 0'>Form Answers</th></tr></thead>";
        $text .= "<tbody>";
        foreach ($fields as $key => $value) {
            $text .= "<tr style='border-bottom: 1px solid #eee;'>";
            $text .= "<td style='border-right: 1px solid #eee; padding: 10px; width: 30%;'>". htmlspecialchars($key) ."</td> 
                <td style='padding: 10px; text-align: left'>" . htmlspecialchars($value) ."</td>";
            $text .= "</tr>";
        }
        $text .= "</tbody>";
        $text .= "</table>";

        return $text;
    }
}
