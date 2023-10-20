<?php
namespace eGamings\WLC\Converters;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfConverter
{

    public static function toPdf(string $text) {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf(
            $options
        );

        $dompdf->loadHtml($text, 'UTF-8');

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render(); // render as PDF

        $fileName = 'terms_and_conditions';

        $dompdf->stream($fileName, array("Attachment" => true));

        exit();
    }
}
