<?php
namespace eGamings\WLC\Converters;

use Dompdf\Options;
use Dompdf\Dompdf;

class FormToFileConverter
{

    public static function toPdf(string $text, string $fileName) {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf(
            $options
        );

        $dompdf->loadHtml($text, 'UTF-8');

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render(); // render as PDF

        return $dompdf->output();
    }
}
