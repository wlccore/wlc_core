<?php

namespace eGamings\WLC;

use eGamings\WLC\Logger;

class LabelOutput
{
    private $labelMeta;
    private $metrics = [];
    private $output = "";
    private $parsed_labels = []; # to work with the labels
    private $redis = null;

    public function __construct($labelMeta)
    {
        $this->redis = System::redis();

        $this->labelMeta = $labelMeta;
        $this->metrics = $this->redis->keys("{$this->labelMeta->getNamespace()}*");
        $this->generateOutput();
    }

    private function generateOutput(): void
    {
        $output = "#HELP {$this->labelMeta->getNamespace()} {$this->labelMeta->getDescription()} \n" .
        "#TYPE {$this->labelMeta->getNamespace()} {$this->labelMeta->getType()} \n";

        if (count($this->metrics) > 0) {
            foreach ($this->metrics as $key => $metric) {
                # get the labels which are between the enclosing curly braces
                $tmp_array = [];
                $output_labels = "";

                preg_match('/[^{}]+(?=})/', $metric, $tmp_array);

                # if there is no match, pass
                if (empty($tmp_array[0])) {
                    continue;
                }

                # transform the string representation to the array one
                $s_labels = $tmp_array[0];
                $a_labels = explode(',', $s_labels);
                foreach ($a_labels as $key => $value) {
                    $l = explode('=', $value);
                    $this->parsed_labels[$l[0]] = $l[1];
                }

                # add labels that are for the output only
                $this->parsed_labels['instance'] = '"' . Logger::getInstanceName() . '"';
                if (empty($this->parsed_labels['hostname'])) {
                    $this->parsed_labels['hostname'] = '"' . gethostname() . '"';
                }

                $new_array = [];
                foreach ($this->parsed_labels as $label => $value) {
                    $value = trim($value);
                    $label = trim($label);

                    if(!$value) {
                        $value = "NULL";
                    }
                    $new_array[] = "$label=$value";
                }

                $output_labels = implode(',', $new_array);

                $count = (int)$this->redis->get("{$this->labelMeta->getNamespace()}{{$s_labels}}");
                $output .= "{$this->labelMeta->getNamespace()}{{$output_labels}} $count \n";
            }
        } else {
            $output = '';
        }

        if (strlen($output) > 0) {
            $output .= "\n";
        }

        $this->output = $output;
    }

     /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }
}
